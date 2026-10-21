<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\Admin;
use App\Models\AdminTransaction;
use App\Models\Announcement;
use App\Helpers\MoreThanPanelHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Get paginated users with wallet relationship
        $usersPaginated = User::with('wallet')->latest()->paginate(15);
        $users = $usersPaginated;
        
        // Get paginated transactions for display
        $transactionsPaginated = Transaction::with('user')->latest()->paginate(15);
        $transactions = $transactionsPaginated;
        
        // Get ALL transactions for stats calculations (not paginated)
        $allTransactions = Transaction::with('user')->latest()->get();
        
        // Define success statuses (case-insensitive) and filter collection
        $successfulStatuses = ['success', 'completed'];
        $successfulTx = $allTransactions->filter(function ($t) use ($successfulStatuses) {
            return in_array(strtolower($t->status ?? ''), $successfulStatuses);
        });

        // Exclude failed, expired, refunded, cancelled from turnover
        $excludeStatuses = ['failed', 'expired', 'refunded', 'cancelled'];
        $turnoverTx = $allTransactions->filter(function ($t) use ($excludeStatuses, $successfulStatuses) {
            $status = strtolower($t->status ?? '');
            return in_array($status, $successfulStatuses) && !in_array($status, $excludeStatuses);
        });

        // Compute profit: for each successful transaction, estimate provider cost and subtract from amount
        $exchangeRate = config('services.exchange_rate', 1500);
        $totalProfit = 0.0;
        $todayProfit = 0.0;
        $thisWeekProfit = 0.0;
        $thisMonthProfit = 0.0;

        foreach ($successfulTx as $t) {
            $providerCost = $this->estimateProviderCost($t, $exchangeRate);
            $profit = round(floatval($t->amount) - floatval($providerCost), 2);
            $totalProfit += $profit;

            $created = $t->created_at;
            if ($created->isToday()) {
                $todayProfit += $profit;
            }
            if ($created->between(now()->startOfWeek(), now()->endOfWeek())) {
                $thisWeekProfit += $profit;
            }
            if ($created->isSameMonth(now())) {
                $thisMonthProfit += $profit;
            }
        }

        // Additional stats for the dashboard
        $stats = [
            'total_users' => User::count(),
            // Only include amounts from successful transactions and exclude failed/expired/refunded/cancelled
            'total_revenue' => $turnoverTx->sum('amount'),
            'total_transactions' => $turnoverTx->count(),
            'pending_requests' => $allTransactions->filter(fn($t) => strtolower($t->status ?? '') === 'pending')->count(),
            'successful_transactions' => $successfulTx->count(),
            'active_users_30_days' => User::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            'latest_user' => User::latest()->first(),
            'latest_transaction' => Transaction::with('user')->latest()->first(),
            'total_giftcard_withdrawable' => (float) DB::table('users')->sum('withdrawable_balance'),
            'total_profit' => round($totalProfit, 2),
            'profit_today' => round($todayProfit, 2),
            'profit_week' => round($thisWeekProfit, 2),
            'profit_month' => round($thisMonthProfit, 2),
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

    public function saveAnnouncement(Request $request)
    {
        $request->validate([
            'announcement_id' => 'nullable|exists:announcements,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $data = [
            'title' => $request->title,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active'),
            'starts_at' => $request->starts_at ?: null,
            'ends_at' => $request->ends_at ?: null,
            'created_by' => Auth::guard('admins')->id(),
        ];

        if ($request->announcement_id) {
            $announcement = Announcement::find($request->announcement_id);
            if ($announcement) {
                $announcement->update($data);
                return back()->with('success', 'Announcement updated successfully.');
            }
        }

        Announcement::create($data);
        return back()->with('success', 'Announcement saved successfully.');
    }

    public function toggleAnnouncement(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);

        return back()->with('success', 'Announcement visibility updated.');
    }

    public function destroyAnnouncement(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted successfully.');
    }

    public function getSystemStats()
    {
        // For system stats we only want to count finalised successful transactions for revenue/counters

        $exchangeRate = config('services.exchange_rate', 1500);

        // Profit calculations
        $todayProfit = 0.0;
        $thisWeekProfit = 0.0;
        $thisMonthProfit = 0.0;

        $todayTx = Transaction::whereDate('created_at', today())
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->get();
        foreach ($todayTx as $t) {
            $todayProfit += round(floatval($t->amount) - $this->estimateProviderCost($t, $exchangeRate), 2);
        }

        $weekTx = Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->get();
        foreach ($weekTx as $t) {
            $thisWeekProfit += round(floatval($t->amount) - $this->estimateProviderCost($t, $exchangeRate), 2);
        }

        $monthTx = Transaction::whereMonth('created_at', now()->month)
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->get();
        foreach ($monthTx as $t) {
            $thisMonthProfit += round(floatval($t->amount) - $this->estimateProviderCost($t, $exchangeRate), 2);
        }

        $stats = [
            'users' => [
                'total' => User::count(),
                'active_today' => User::whereDate('last_login_at', today())->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            'transactions' => [
                // 'total' here represents completed/successful transactions only
                'total' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->count(),
                'total_amount' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->sum('amount'),
                'successful' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->count(),
                'pending' => Transaction::whereRaw("LOWER(COALESCE(status, '')) = 'pending'")->count(),
                'failed' => Transaction::whereRaw("LOWER(COALESCE(status, '')) = 'failed'")->count(),
            ],
            'revenue' => [
                'today' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->whereDate('created_at', today())->sum('amount'),
                'this_week' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->sum('amount'),
                'this_month' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->whereMonth('created_at', now()->month)->sum('amount'),
            ],
            'profit' => [
                'today' => round($todayProfit, 2),
                'this_week' => round($thisWeekProfit, 2),
                'this_month' => round($thisMonthProfit, 2),
            ]
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Estimate provider cost for a transaction using heuristics
     */
    private function estimateProviderCost($transaction, $exchangeRate = 1500)
    {
        $mtp = new MoreThanPanelHelper();

        // Normalize response array
        $response = [];
        if (is_array($transaction->response)) {
            $response = $transaction->response;
        } else {
            $response = json_decode($transaction->response ?? '[]', true) ?? [];
        }

        // 1) If original USD cost exists, use it
        if (!empty($response['original_cost_usd'])) {
            return round(floatval($response['original_cost_usd']) * $exchangeRate, 2);
        }

        // 2) Use rate_per_k (USD per 1000 units) and quantity if present
        if (!empty($response['rate_per_k']) && !empty($response['quantity'])) {
            $ratePerK = floatval($response['rate_per_k']);
            $quantity = intval($response['quantity']);
            $costUsdPerUnit = $ratePerK / 1000.0;
            $costUsd = round($costUsdPerUnit * $quantity, 4);
            return round($costUsd * $exchangeRate, 2);
        }

        // 3) Use product client_price when available (per-unit NGN)
        $serviceId = $transaction->service_id ?? ($response['service_id'] ?? null);
        $quantity = intval($response['quantity'] ?? ($transaction->quantity ?? 1));
        if ($serviceId) {
            $pricing = $mtp->getProductPricing($serviceId);
            $clientPrice = $pricing['client_price'] ?? null; // per-unit NGN
            if ($clientPrice !== null && $clientPrice > 0) {
                return round(floatval($clientPrice) * $quantity, 2);
            }
        }

        // Unknown provider cost — fallback to zero (profit equals amount)
        return 0.0;
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
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user   = User::findOrFail($userId);
            $wallet = $user->wallet;

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'User wallet not found.',
                ], 404);
            }

            $amount = (float) $request->amount;

            // ── Credit wallet ──────────────────────────────────────────────
            $wallet->increment('balance', $amount);

            // ── Audit transaction ──────────────────────────────────────────
            $admin = Auth::guard('admins')->user();

            $transaction = \App\Models\Transaction::create([
                'user_id'     => $user->id,
                'reference'   => 'ADMIN_CREDIT_' . strtoupper(\Illuminate\Support\Str::random(10)),
                'type'        => 'wallet_funding',
                'channel'     => 'Admin',
                'provider'    => 'Admin',
                'amount'      => $amount,
                'status'      => 'success',
                'description' => $request->description . ' (Admin: ' . ($admin->username ?? $admin->email ?? 'admin') . ')',
            ]);

            // ── AdminTransaction log ───────────────────────────────────────
            \App\Models\AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'ADD_FUNDS',
                'amount'           => $amount,
                'affected_user_id' => $user->id,
                'description'      => $request->description,
                'reference'        => $transaction->reference,
            ]);

            DB::commit();

            // ── Referral commission (outside main transaction so it
            //    never rolls back the deposit if it fails) ──────────────────
            app(\App\Services\ReferralService::class)->creditCommissionOnDeposit(
                $user,
                $amount,
                $transaction->id,
                'admin_credit'
            );

            return response()->json([
                'success' => true,
                'message' => '₦' . number_format($amount, 2) . ' added to ' . $user->username . '\'s wallet successfully.',
                'new_balance' => $wallet->fresh()->balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin addFunds error', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to add funds: ' . $e->getMessage(),
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

            $user  = User::with('wallet')->findOrFail($userId);
            $admin = Auth::guard('admins')->user(); // ✅ corrected guard

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Admin not logged in.'
                ], 403);
            }

            if (!$user->wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a wallet.'
                ], 400);
            }

            $amount = $request->amount;
            $description = $request->description;
            $reference = 'ADMIN_DED_' . strtoupper(Str::random(10));

            // Check sufficient balance
            if ($user->wallet->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Current balance: ₦' . number_format($user->wallet->balance, 2)
                ], 400);
            }

            // Deduct balance
            $oldBalance = $user->wallet->balance;
            $user->wallet->balance -= $amount;
            $user->wallet->save();

            // Log user transaction
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

            // Log admin transaction
            AdminTransaction::create([
                'admin_id' => $admin->id,
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

    public function adjustGiftcardBalance(Request $request, $userId)
    {
        $data = $request->validate([
            'action' => ['required', 'in:add,deduct'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $admin = Auth::guard('admins')->user();
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        try {
            $result = DB::transaction(function () use ($data, $userId, $admin) {
                $user = User::query()->lockForUpdate()->findOrFail($userId);
                $before = round((float) ($user->withdrawable_balance ?? 0), 2);
                $amount = round((float) $data['amount'], 2);

                if ($amount <= 0) {
                    throw ValidationException::withMessages(['amount' => 'Amount must be at least ₦0.01.']);
                }
                if ($data['action'] === 'deduct' && $amount > $before) {
                    throw ValidationException::withMessages(['amount' => 'Insufficient gift-card balance. Available: ₦' . number_format($before, 2)]);
                }

                $after = $data['action'] === 'add' ? $before + $amount : $before - $amount;
                DB::table('users')->where('id', $user->id)->update(['withdrawable_balance' => $after]);

                $reference = 'ADMIN_GC_' . strtoupper(Str::random(16));
                $actionLabel = $data['action'] === 'add' ? 'Added' : 'Deducted';
                $description = sprintf(
                    '%s ₦%s gift-card withdrawable balance. Reason: %s. Balance: ₦%s → ₦%s. Admin: %s',
                    $actionLabel,
                    number_format($amount, 2),
                    $data['description'],
                    number_format($before, 2),
                    number_format($after, 2),
                    $admin->username ?? $admin->email ?? ('Admin #' . $admin->id)
                );

                Transaction::create([
                    'reference' => $reference,
                    'user_id' => $user->id,
                    'type' => 'giftcard_balance_adjustment',
                    'channel' => 'Admin',
                    'provider' => 'Admin',
                    'recipient' => $user->username,
                    'amount' => $amount,
                    'status' => 'success',
                    'description' => $description,
                    'response' => [
                        'action' => $data['action'],
                        'reason' => $data['description'],
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'admin_id' => $admin->id,
                    ],
                ]);

                AdminTransaction::create([
                    'admin_id' => $admin->id,
                    'action' => $data['action'] === 'add' ? 'GIFTCARD_BALANCE_CREDIT' : 'GIFTCARD_BALANCE_DEBIT',
                    'amount' => $amount,
                    'affected_user_id' => $user->id,
                    'description' => $description,
                    'reference' => $reference,
                ]);

                return ['balance' => $after, 'reference' => $reference];
            });

            return response()->json([
                'success' => true,
                'message' => 'Gift-card balance updated successfully.',
                'new_balance' => number_format($result['balance'], 2, '.', ''),
                'reference' => $result['reference'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Admin gift-card balance adjustment failed', [
                'user_id' => $userId,
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not update gift-card balance. No changes were made.'], 500);
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
        $admin = Auth::guard('admins')->user(); // ✅ fixed guard name

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Admin not logged in.'
            ], 403);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Log admin action
        AdminTransaction::create([
            'admin_id' => $admin->id,
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
            $admin = Auth::guard('admins')->user();
            
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
            $user->setAttribute(
                'giftcard_withdrawable_balance',
                (float) DB::table('users')->where('id', $user->id)->value('withdrawable_balance')
            );

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

    /**
     * Block a user account
     */
    public function blockUser(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:5|max:255',
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
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Admin not logged in.'
                ], 403);
            }

            // Check if already blocked
            if ($user->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already blocked.'
                ], 400);
            }

            $reason = $request->reason;

            // Block the user
            $user->block($reason);

            // Log admin action
            AdminTransaction::create([
                'admin_id' => $admin->id,
                'action' => 'BLOCK_USER',
                'amount' => 0,
                'affected_user_id' => $user->id,
                'description' => sprintf(
                    'Blocked user: %s (%s). Reason: %s',
                    $user->username,
                    $user->email,
                    $reason
                ),
                'reference' => 'BLOCK_' . strtoupper(Str::random(8))
            ]);

            // Invalidate user sessions (force logout)
            $this->invalidateUserSessions($user->id);

            DB::commit();

            Log::warning('User blocked by admin', [
                'blocked_user_id' => $user->id,
                'blocked_user_email' => $user->email,
                'admin_id' => $admin->id,
                'reason' => $reason,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "User {$user->username} has been successfully blocked.",
                'is_blocked' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Block user error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to block user.'
            ], 500);
        }
    }

    /**
     * Unblock a user account
     */
    public function unblockUser(Request $request, $userId)
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($userId);
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Admin not logged in.'
                ], 403);
            }

            // Check if not blocked
            if (!$user->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not blocked.'
                ], 400);
            }

            $previousReason = $user->blocked_reason;

            // Unblock the user
            $user->unblock();

            // Log admin action
            AdminTransaction::create([
                'admin_id' => $admin->id,
                'action' => 'UNBLOCK_USER',
                'amount' => 0,
                'affected_user_id' => $user->id,
                'description' => sprintf(
                    'Unblocked user: %s (%s). Previous reason: %s',
                    $user->username,
                    $user->email,
                    $previousReason ?? 'No reason provided'
                ),
                'reference' => 'UNBLOCK_' . strtoupper(Str::random(8))
            ]);

            DB::commit();

            Log::info('User unblocked by admin', [
                'unblocked_user_id' => $user->id,
                'unblocked_user_email' => $user->email,
                'admin_id' => $admin->id,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "User {$user->username} has been successfully unblocked.",
                'is_blocked' => false
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unblock user error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock user.'
            ], 500);
        }
    }

    /**
     * Get user blocking status
     */
    public function getUserBlockStatus($userId)
    {
        try {
            $user = User::findOrFail($userId);

            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_blocked' => $user->is_blocked,
                'blocked_reason' => $user->blocked_reason,
                'blocked_at' => $user->blocked_at,
                'last_login_at' => $user->last_login_at
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Invalidate all sessions for a user
     * This forces them to log out across all devices
     */
    private function invalidateUserSessions($userId): void
    {
        try {
            // Delete all sessions for this user
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();

            Log::info('User sessions invalidated', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate user sessions: ' . $e->getMessage());
        }
    }

    /**
     * Get all services management page
     */
    public function manageServices()
    {
        try {
            // Ensure the 'logs' service exists so admins can enable/disable it from the services page
            Service::firstOrCreate(
                ['slug' => 'logs'],
                ['name' => 'Logs', 'is_active' => true]
            );

            $services = Service::all();

            return view('admin.services', compact('services'));
        } catch (\Exception $e) {
            Log::error('Error loading services page: ' . $e->getMessage());
            return back()->with('error', 'Failed to load services');
        }
    }

    /**
     * Toggle service active/inactive status with proper transaction handling
     */
    public function toggleService(Request $request, $serviceId)
    {
        try {
            DB::beginTransaction();

            // Force a fresh read from database - use locking to prevent race conditions
            $service = Service::lockForUpdate()->findOrFail($serviceId);
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Admin not logged in.'
                ], 403);
            }

            $oldStatus = $service->is_active;
            $newStatus = !$service->is_active;
            
            // Update the service
            $service->is_active = $newStatus;
            $service->save();

            $action = $newStatus ? 'ACTIVATE_SERVICE' : 'DEACTIVATE_SERVICE';
            $description = sprintf(
                '%s service: %s (ID: %d). Status: %s → %s',
                $newStatus ? 'Activated' : 'Deactivated',
                $service->name,
                $service->id,
                $oldStatus ? 'Active' : 'Inactive',
                $newStatus ? 'Active' : 'Inactive'
            );

            // Log admin action
            AdminTransaction::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'amount' => 0,
                'affected_user_id' => null,
                'description' => $description,
                'reference' => 'SVC_' . strtoupper(Str::random(8))
            ]);

            DB::commit();

            // Log the change
            Log::info('Service status toggled successfully', [
                'admin_id' => $admin->id,
                'service_id' => $serviceId,
                'service_name' => $service->name,
                'new_status' => $newStatus,
                'old_status' => $oldStatus,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => $newStatus ? "✓ {$service->name} has been activated" : "✓ {$service->name} has been deactivated",
                'is_active' => $newStatus,
                'service_id' => $service->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Toggle service error', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update service status. Please try again.'
            ], 500);
        }
    }
}