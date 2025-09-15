<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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

    public function getUserDetails($id)
    {
        try {
            $user = User::with(['wallet', 'transactions' => function($query) {
                $query->latest()->limit(50); // Limit to last 50 transactions
            }])->findOrFail($id);

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

    public function updateUserPassword(Request $request, $id)
    {
        try {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::findOrFail($id);
            $user->password = Hash::make($request->password);
            $user->save();

            // Log the admin action (optional)
            \Log::info("Admin updated password for user: {$user->username}", [
                'admin' => auth()->user()->username ?? 'system',
                'user_id' => $id,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['password'] ?? ['Invalid input']),
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Failed to update user password: " . $e->getMessage(), [
                'user_id' => $id,
                'admin' => auth()->user()->username ?? 'system'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password. Please try again.',
            ], 500);
        }
    }

    public function updateUserPin(Request $request, $id)
    {
        try {
            $request->validate([
                'pin' => ['required', 'digits:4'],
            ]);

            $user = User::findOrFail($id);
            $user->pin = Hash::make($request->pin);
            $user->save();

            // Log the admin action (optional)
            \Log::info("Admin updated PIN for user: {$user->username}", [
                'admin' => auth()->user()->username ?? 'system',
                'user_id' => $id,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIN updated successfully.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'PIN must be exactly 4 digits.',
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Failed to update user PIN: " . $e->getMessage(), [
                'user_id' => $id,
                'admin' => auth()->user()->username ?? 'system'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PIN. Please try again.',
            ], 500);
        }
    }

    // Additional helper methods for admin functionality

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
}