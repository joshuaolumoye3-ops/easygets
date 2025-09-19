<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Get paginated users with wallet relationship
        $users = User::with('wallet')->latest()->paginate(10);
        
        // Get all transactions for calculations (not just 10)
        $transactions = Transaction::with('user')->latest()->get();
        
        // Additional stats for the dashboard
        $stats = [
            'total_users' => $users->total(), // Use total() for paginated count
            'total_revenue' => $transactions->sum('amount'),
            'total_transactions' => $transactions->count(),
            'pending_requests' => $transactions->where('status', 'Pending')->count(),
            'successful_transactions' => $transactions->where('status', 'Success')->count(),
            'active_users_30_days' => User::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            'latest_user' => User::latest()->first(),
            'latest_transaction' => Transaction::with('user')->latest()->first(),
        ];

        return view('admin.index', compact('users', 'transactions', 'stats'));
    }

   
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:admins,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:super_admin,admin',
        ]);

        Admin::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'active',
        ]);

        return back()->with('success', 'New admin created successfully.');
    }

    public function searchUsers(Request $request)
    {
        $query = $request->get('query');
        
        $users = User::with('wallet')
            ->where('username', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('phone_number', 'like', "%{$query}%")
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    public function getSystemStats()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active_today' => User::whereDate('last_login_at', today())->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            'transactions' => [
                'total' => Transaction::count(),
                'total_amount' => Transaction::sum('amount'),
                'successful' => Transaction::where('status', 'Success')->count(),
                'pending' => Transaction::where('status', 'Pending')->count(),
                'failed' => Transaction::where('status', 'Failed')->count(),
            ],
            'revenue' => [
                'today' => Transaction::whereDate('created_at', today())->sum('amount'),
                'this_week' => Transaction::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->sum('amount'),
                'this_month' => Transaction::whereMonth('created_at', now()->month)->sum('amount'),
            ]
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Log the deletion
            \Log::warning("Admin deleted user: {$user->username}", [
                'admin' => auth()->user()->username ?? 'system',
                'user_id' => $id,
                'user_email' => $user->email,
                'timestamp' => now()
            ]);
            
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.'
            ], 500);
        }
    }

    public function toggleUserStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->is_active = !$user->is_active;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.',
                'status' => $user->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status.'
            ], 500);
        }
    }

    /**
     * Add funds to user's wallet with admin transaction logging
     */
public function addFunds(Request $request, $userId)
{
    $validator = Validator::make($request->all(), [
        'amount'      => 'required|numeric|min:0.01',
        'description' => 'required|string|min:3|max:255'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        DB::beginTransaction();

        $user  = User::with('wallet')->findOrFail($userId);
        $admin = Auth::guard('admins')->user(); // ✅ track the logged-in admin

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Admin not logged in.'
            ], 403);
        }

        // Create wallet if it doesn't exist
        if (!$user->wallet) {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0
            ]);
            $user->load('wallet');
        }

        $amount      = $request->amount;
        $description = $request->description;
        $reference   = 'ADMIN_ADD_' . strtoupper(Str::random(10));

        // Update wallet balance
        $oldBalance = $user->wallet->balance;
        $user->wallet->balance += $amount;
        $user->wallet->save();

        // Create transaction record for the user
        Transaction::create([
            'reference' => $reference,
            'user_id'   => $user->id,
            'type'      => 'Admin Credit',
            'channel'   => 'Admin',
            'provider'  => 'System',
            'phone'     => $user->phone_number,
            'recipient' => $user->username,
            'amount'    => $amount,
            'status'    => 'Success',
            'response'  => $description
        ]);

        // Create transaction record for the admin
        AdminTransaction::create([
            'admin_id'         => $admin->id,
            'action'           => 'ADD_FUNDS',
            'amount'           => $amount,
            'affected_user_id' => $user->id,
            'description'      => sprintf(
                'Added ₦%s to %s\'s wallet. Reason: %s. Balance: ₦%s → ₦%s',
                number_format($amount, 2),
                $user->username,
                $description,
                number_format($oldBalance, 2),
                number_format($user->wallet->balance, 2)
            ),
            'reference'        => $reference,
        ]);

        DB::commit();

        return response()->json([
            'success'     => true,
            'message'     => "₦" . number_format($amount, 2) . " has been successfully added to {$user->username}'s wallet",
            'new_balance' => number_format($user->wallet->balance, 2)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Add funds error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to add funds. Please try again.'
        ], 500);
    }
}


    /**
     * Deduct funds from user's wallet with admin transaction logging
     */
    public function deductFunds(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:3|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::with('wallet')->findOrFail($userId);
            $admin = Auth::guard('admin')->user();
            
            if (!$user->wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a wallet'
                ], 400);
            }

            $amount = $request->amount;
            $description = $request->description;
            $reference = 'ADMIN_DED_' . strtoupper(Str::random(10));

            // Check if user has sufficient balance
            if ($user->wallet->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Current balance: ₦' . number_format($user->wallet->balance, 2)
                ], 400);
            }

            // Update wallet balance
            $oldBalance = $user->wallet->balance;
            $user->wallet->balance -= $amount;
            $user->wallet->save();

            // Create user transaction record
            Transaction::create([
                'reference' => $reference,
                'user_id' => $user->id,
                'type' => 'Admin Debit',
                'channel' => 'Admin',
                'provider' => 'System',
                'phone' => $user->phone_number,
                'recipient' => $user->username,
                'amount' => $amount,
                'status' => 'Success',
                'response' => $description
            ]);

            // Create admin transaction record
            AdminTransaction::create([
                'admin_id' => $admin ? $admin->id : null,
                'action' => 'DEDUCT_FUNDS',
                'amount' => $amount,
                'affected_user_id' => $user->id,
                'description' => sprintf(
                    'Deducted ₦%s from %s\'s wallet. Reason: %s. Balance: ₦%s → ₦%s',
                    number_format($amount, 2),
                    $user->username,
                    $description,
                    number_format($oldBalance, 2),
                    number_format($user->wallet->balance, 2)
                ),
                'reference' => $reference
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "₦" . number_format($amount, 2) . " has been successfully deducted from {$user->username}'s wallet",
                'new_balance' => number_format($user->wallet->balance, 2)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Deduct funds error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to deduct funds. Please try again.'
            ], 500);
        }
    }

    /**
     * Update user password with admin transaction logging
     */
    public function updateUserPassword(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($userId);
            $admin = Auth::guard('admin')->user();
            
            $user->password = Hash::make($request->password);
            $user->save();

            // Create admin transaction record for password change
            AdminTransaction::create([
                'admin_id' => $admin ? $admin->id : null,
                'action' => 'UPDATE_PASSWORD',
                'amount' => 0,
                'affected_user_id' => $user->id,
                'description' => sprintf(
                    'Updated password for user: %s (%s)',
                    $user->username,
                    $user->email
                ),
                'reference' => 'PWD_' . strtoupper(Str::random(8))
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update password error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password'
            ], 500);
        }
    }

    /**
     * Update user PIN with admin transaction logging
     */
    public function updateUserPin(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'PIN must be exactly 4 digits'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($userId);
            $admin = Auth::guard('admin')->user();
            
            $user->pin = Hash::make($request->pin);
            $user->save();

            // Create admin transaction record for PIN change
            AdminTransaction::create([
                'admin_id' => $admin ? $admin->id : null,
                'action' => 'UPDATE_PIN',
                'amount' => 0,
                'affected_user_id' => $user->id,
                'description' => sprintf(
                    'Updated PIN for user: %s (%s)',
                    $user->username,
                    $user->email
                ),
                'reference' => 'PIN_' . strtoupper(Str::random(8))
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'PIN updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update PIN error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PIN'
            ], 500);
        }
    }

    /**
     * Get user details with transactions
     */
    public function getUserDetails($userId)
    {
        try {
            $user = User::with(['wallet', 'transactions' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            }])->findOrFail($userId);

            return response()->json([
                'success' => true,
                'user' => $user,
                'transactions' => $user->transactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Get admin transactions (optional - for admin transaction history page)
     */
    public function getAdminTransactions()
    {
        $transactions = AdminTransaction::with(['admin', 'affectedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * Get admin transaction history for current admin
     */
    public function getMyTransactions()
    {
        $admin = Auth::guard('admin')->user();
        
        $transactions = AdminTransaction::with(['affectedUser'])
            ->where('admin_id', $admin->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }
}