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
use App\Models\AdminTransaction;
use App\Helpers\MoreThanPanelHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminDashboard extends Controller
{
    /**
     * Admin Dashboard — renamed from dashboard() to avoid conflict
     * with the parent Controller::dashboard() method.
     * Update your route to use: [AdminController::class, 'index']
     */
    public function index(Request $request)
    {
        // ── Per-page values (validated to safe list) ──────────────────
        $uPP  = in_array((int) $request->get('u_pp',  10), [10, 25, 50]) ? (int) $request->get('u_pp',  10) : 10;
        $txPP = in_array((int) $request->get('tx_pp', 10), [10, 25, 50]) ? (int) $request->get('tx_pp', 10) : 10;

        // ── Users (with optional search + date filter) ────────────────
        $uSearch      = trim($request->get('u_search', ''));
        $uRange       = trim($request->get('u_range', ''));
        $uStart       = trim($request->get('u_start', ''));
        $uEnd         = trim($request->get('u_end', ''));
        $uDateLabel   = '';

        $userQuery = User::with('wallet')->latest();
        if ($uSearch !== '') {
            $userQuery->where(function ($q) use ($uSearch) {
                $q->where('username',     'like', "%{$uSearch}%")
                  ->orWhere('email',        'like', "%{$uSearch}%")
                  ->orWhere('phone_number', 'like', "%{$uSearch}%");
            });
        }

        if ($uRange || $uStart || $uEnd) {
            $from = null;
            $to   = null;

            switch ($uRange) {
                case 'today':
                    $from = today()->startOfDay();
                    $to   = today()->endOfDay();
                    $uDateLabel = 'Today';
                    break;
                case 'this_week':
                    $from = now()->startOfWeek();
                    $to   = now()->endOfWeek();
                    $uDateLabel = 'This week';
                    break;
                case 'last_week':
                    $from = now()->subWeek()->startOfWeek();
                    $to   = now()->subWeek()->endOfWeek();
                    $uDateLabel = 'Last week';
                    break;
                case 'this_month':
                    $from = now()->startOfMonth();
                    $to   = now()->endOfMonth();
                    $uDateLabel = 'This month';
                    break;
                case 'last_month':
                    $from = now()->subMonthNoOverflow()->startOfMonth();
                    $to   = now()->subMonthNoOverflow()->endOfMonth();
                    $uDateLabel = 'Last month';
                    break;
                case 'this_year':
                    $from = now()->startOfYear();
                    $to   = now()->endOfYear();
                    $uDateLabel = 'This year';
                    break;
                case 'custom':
                default:
                    if ($uStart) {
                        try {
                            $from = Carbon::parse($uStart)->startOfDay();
                        } catch (\Exception $e) {
                            $from = null;
                        }
                    }
                    if ($uEnd) {
                        try {
                            $to = Carbon::parse($uEnd)->endOfDay();
                        } catch (\Exception $e) {
                            $to = null;
                        }
                    }
                    if ($from && !$to) {
                        $to = (clone $from)->endOfDay();
                    }
                    if (!$from && $to) {
                        $from = (clone $to)->startOfDay();
                    }
                    if ($from && $to) {
                        if ($from->gt($to)) {
                            [$from, $to] = [$to, $from];
                        }
                        $uDateLabel = $from->format('d M Y') . ' – ' . $to->format('d M Y');
                    }
                    break;
            }

            if ($from && $to) {
                $userQuery->whereBetween('created_at', [$from, $to]);
            }
        }

        $users = $userQuery->paginate($uPP)->withQueryString();

        // ── Transactions (with optional search + date filter) ─────────
        $txSearch    = trim($request->get('tx_search', ''));
        $txRange     = trim($request->get('tx_range', ''));
        $txStart     = trim($request->get('tx_start', ''));
        $txEnd       = trim($request->get('tx_end', ''));
        $txDateLabel = '';

        $txQuery = Transaction::with('user')->latest();
        if ($txSearch !== '') {
            $txQuery->where(function ($q) use ($txSearch) {
                $q->where('type',      'like', "%{$txSearch}%")
                  ->orWhere('status',  'like', "%{$txSearch}%")
                  ->orWhere('reference', 'like', "%{$txSearch}%")
                  ->orWhereHas('user', function ($uq) use ($txSearch) {
                      $uq->where('username', 'like', "%{$txSearch}%")
                         ->orWhere('email',  'like', "%{$txSearch}%");
                  });
            });
        }

        if ($txRange || $txStart || $txEnd) {
            $from = null;
            $to   = null;

            switch ($txRange) {
                case 'today':
                    $from = today()->startOfDay();
                    $to   = today()->endOfDay();
                    $txDateLabel = 'Today';
                    break;
                case 'this_week':
                    $from = now()->startOfWeek();
                    $to   = now()->endOfWeek();
                    $txDateLabel = 'This week';
                    break;
                case 'last_week':
                    $from = now()->subWeek()->startOfWeek();
                    $to   = now()->subWeek()->endOfWeek();
                    $txDateLabel = 'Last week';
                    break;
                case 'this_month':
                    $from = now()->startOfMonth();
                    $to   = now()->endOfMonth();
                    $txDateLabel = 'This month';
                    break;
                case 'last_month':
                    $from = now()->subMonthNoOverflow()->startOfMonth();
                    $to   = now()->subMonthNoOverflow()->endOfMonth();
                    $txDateLabel = 'Last month';
                    break;
                case 'this_year':
                    $from = now()->startOfYear();
                    $to   = now()->endOfYear();
                    $txDateLabel = 'This year';
                    break;
                case 'custom':
                default:
                    if ($txStart) {
                        try {
                            $from = Carbon::parse($txStart)->startOfDay();
                        } catch (\Exception $e) {
                            $from = null;
                        }
                    }
                    if ($txEnd) {
                        try {
                            $to = Carbon::parse($txEnd)->endOfDay();
                        } catch (\Exception $e) {
                            $to = null;
                        }
                    }
                    if ($from && !$to) {
                        $to = (clone $from)->endOfDay();
                    }
                    if (!$from && $to) {
                        $from = (clone $to)->startOfDay();
                    }

                    if ($from && $to) {
                        if ($from->gt($to)) {
                            [$from, $to] = [$to, $from];
                        }
                        $txDateLabel = $from->format('d M Y') . ' – ' . $to->format('d M Y');
                    }
                    break;
            }

            if ($from && $to) {
                $txQuery->whereBetween('created_at', [$from, $to]);
            }
        }

        if (empty($txDateLabel)) {
            $txDateLabel = 'All time';
        }

        $transactionsTotalAmount = (clone $txQuery)->sum('amount');
        $transactionsTotalCount  = (clone $txQuery)->count();
        $transactions            = $txQuery->paginate($txPP)->withQueryString();

        // ── Stats (computed from the full dataset, not paginated) ──────
        $allTransactions    = Transaction::with('user')->get();
        $successfulStatuses = ['success', 'completed'];
        $excludeStatuses    = ['failed', 'expired', 'refunded', 'cancelled'];

        $successfulTx = $allTransactions->filter(
            fn($t) => in_array(strtolower($t->status ?? ''), $successfulStatuses)
        );

        $turnoverTx = $allTransactions->filter(
            fn($t) => in_array(strtolower($t->status ?? ''), $successfulStatuses)
                   && !in_array(strtolower($t->status ?? ''), $excludeStatuses)
        );

        $exchangeRate    = config('services.exchange_rate', 1500);
        $totalProfit     = 0.0;
        $todayProfit     = 0.0;
        $thisWeekProfit  = 0.0;
        $thisMonthProfit = 0.0;

        foreach ($successfulTx as $t) {
            $providerCost = $this->estimateProviderCost($t, $exchangeRate);
            $profit       = round(floatval($t->amount) - floatval($providerCost), 2);
            $totalProfit += $profit;

            $created = $t->created_at;
            if ($created->isToday())                                           $todayProfit     += $profit;
            if ($created->between(now()->startOfWeek(), now()->endOfWeek()))   $thisWeekProfit  += $profit;
            if ($created->isSameMonth(now()))                                  $thisMonthProfit += $profit;
        }

        $stats = [
            'total_users'             => User::count(),
            'total_revenue'           => $turnoverTx->sum('amount'),
            'total_transactions'      => $turnoverTx->count(),
            'pending_requests'        => $allTransactions->filter(fn($t) => strtolower($t->status ?? '') === 'pending')->count(),
            'successful_transactions' => $successfulTx->count(),
            'active_users_30_days'    => User::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            'latest_user'             => User::latest()->first(),
            'latest_transaction'      => Transaction::with('user')->latest()->first(),
            'total_profit'            => round($totalProfit,    2),
            'profit_today'            => round($todayProfit,    2),
            'profit_week'             => round($thisWeekProfit, 2),
            'profit_month'            => round($thisMonthProfit, 2),
        ];

        return view('admin.index', compact(
            'users',
            'transactions',
            'stats',
            'txDateLabel',
            'transactionsTotalAmount',
            'transactionsTotalCount',
            'uDateLabel'
        ));
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
            'users'   => $users
        ]);
    }

    public function getSystemStats()
    {
        $exchangeRate    = config('services.exchange_rate', 1500);
        $todayProfit     = 0.0;
        $thisWeekProfit  = 0.0;
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
                'total'          => User::count(),
                'active_today'   => User::whereDate('last_login_at', today())->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            'transactions' => [
                'total'        => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->count(),
                'total_amount' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->sum('amount'),
                'successful'   => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->count(),
                'pending'      => Transaction::whereRaw("LOWER(COALESCE(status, '')) = 'pending'")->count(),
                'failed'       => Transaction::whereRaw("LOWER(COALESCE(status, '')) = 'failed'")->count(),
            ],
            'revenue' => [
                'today'      => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->whereDate('created_at', today())->sum('amount'),
                'this_week'  => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
                'this_month' => Transaction::whereRaw("LOWER(COALESCE(status, '')) IN ('success','completed')")->whereMonth('created_at', now()->month)->sum('amount'),
            ],
            'profit' => [
                'today'     => round($todayProfit,    2),
                'this_week' => round($thisWeekProfit, 2),
                'this_month'=> round($thisMonthProfit, 2),
            ]
        ];

        return response()->json(['success' => true, 'stats' => $stats]);
    }

    private function estimateProviderCost($transaction, $exchangeRate = 1500)
    {
        $mtp = new MoreThanPanelHelper();

        $response = [];
        if (is_array($transaction->response)) {
            $response = $transaction->response;
        } else {
            $response = json_decode($transaction->response ?? '[]', true) ?? [];
        }

        if (!empty($response['original_cost_usd'])) {
            return round(floatval($response['original_cost_usd']) * $exchangeRate, 2);
        }

        if (!empty($response['rate_per_k']) && !empty($response['quantity'])) {
            $costUsd = round((floatval($response['rate_per_k']) / 1000.0) * intval($response['quantity']), 4);
            return round($costUsd * $exchangeRate, 2);
        }

        $serviceId = $transaction->service_id ?? ($response['service_id'] ?? null);
        $quantity  = intval($response['quantity'] ?? ($transaction->quantity ?? 1));
        if ($serviceId) {
            $pricing     = $mtp->getProductPricing($serviceId);
            $clientPrice = $pricing['client_price'] ?? null;
            if ($clientPrice !== null && $clientPrice > 0) {
                return round(floatval($clientPrice) * $quantity, 2);
            }
        }

        return 0.0;
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            Log::warning("Admin deleted user: {$user->username}", [
                'admin'      => auth()->user()->username ?? 'system',
                'user_id'    => $id,
                'user_email' => $user->email,
                'timestamp'  => now()
            ]);
            $user->delete();
            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete user.'], 500);
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
                'status'  => $user->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update user status.'], 500);
        }
    }

    public function addFunds(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|min:3|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            $user  = User::with('wallet')->findOrFail($userId);
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Admin not logged in.'], 403);
            }

            if (!$user->wallet) {
                Wallet::create(['user_id' => $user->id, 'balance' => 0]);
                $user->load('wallet');
            }

            $amount    = $request->amount;
            $reference = 'ADMIN_ADD_' . strtoupper(Str::random(10));
            $oldBalance = $user->wallet->balance;
            $user->wallet->balance += $amount;
            $user->wallet->save();

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
                'response'  => $request->description
            ]);

            AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'ADD_FUNDS',
                'amount'           => $amount,
                'affected_user_id' => $user->id,
                'description'      => sprintf('Added ₦%s to %s\'s wallet. Reason: %s. Balance: ₦%s → ₦%s',
                    number_format($amount, 2), $user->username, $request->description,
                    number_format($oldBalance, 2), number_format($user->wallet->balance, 2)),
                'reference'        => $reference,
            ]);

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => "₦" . number_format($amount, 2) . " added to {$user->username}'s wallet",
                'new_balance' => number_format($user->wallet->balance, 2)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Add funds error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add funds. Please try again.'], 500);
        }
    }

    public function deductFunds(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|min:3|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            $user  = User::with('wallet')->findOrFail($userId);
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Admin not logged in.'], 403);
            }

            if (!$user->wallet) {
                return response()->json(['success' => false, 'message' => 'User does not have a wallet.'], 400);
            }

            if ($user->wallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Current balance: ₦' . number_format($user->wallet->balance, 2)
                ], 400);
            }

            $amount    = $request->amount;
            $reference = 'ADMIN_DED_' . strtoupper(Str::random(10));
            $oldBalance = $user->wallet->balance;
            $user->wallet->balance -= $amount;
            $user->wallet->save();

            Transaction::create([
                'reference' => $reference,
                'user_id'   => $user->id,
                'type'      => 'Admin Debit',
                'channel'   => 'Admin',
                'provider'  => 'System',
                'phone'     => $user->phone_number,
                'recipient' => $user->username,
                'amount'    => $amount,
                'status'    => 'Success',
                'response'  => $request->description
            ]);

            AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'DEDUCT_FUNDS',
                'amount'           => $amount,
                'affected_user_id' => $user->id,
                'description'      => sprintf('Deducted ₦%s from %s\'s wallet. Reason: %s. Balance: ₦%s → ₦%s',
                    number_format($amount, 2), $user->username, $request->description,
                    number_format($oldBalance, 2), number_format($user->wallet->balance, 2)),
                'reference'        => $reference
            ]);

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => "₦" . number_format($amount, 2) . " deducted from {$user->username}'s wallet",
                'new_balance' => number_format($user->wallet->balance, 2)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deduct funds error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to deduct funds. Please try again.'], 500);
        }
    }

    public function updateUserPassword(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            $user  = User::findOrFail($userId);
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Admin not logged in.'], 403);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'UPDATE_PASSWORD',
                'amount'           => 0,
                'affected_user_id' => $user->id,
                'description'      => "Updated password for user: {$user->username} ({$user->email})",
                'reference'        => 'PWD_' . strtoupper(Str::random(8))
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Password updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update password error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update password'], 500);
        }
    }

    public function updateUserPin(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'PIN must be exactly 4 digits'], 422);
        }

        try {
            DB::beginTransaction();

            $user  = User::findOrFail($userId);
            $admin = Auth::guard('admins')->user();

            $user->pin = Hash::make($request->pin);
            $user->save();

            AdminTransaction::create([
                'admin_id'         => $admin ? $admin->id : null,
                'action'           => 'UPDATE_PIN',
                'amount'           => 0,
                'affected_user_id' => $user->id,
                'description'      => "Updated PIN for user: {$user->username} ({$user->email})",
                'reference'        => 'PIN_' . strtoupper(Str::random(8))
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'PIN updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update PIN error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update PIN'], 500);
        }
    }

    public function getUserDetails($userId)
    {
        try {
            $user = User::with(['wallet', 'transactions' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            }])->findOrFail($userId);

            return response()->json([
                'success'      => true,
                'user'         => $user,
                'transactions' => $user->transactions
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    }

    public function getAdminTransactions()
    {
        $transactions = AdminTransaction::with(['admin', 'affectedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

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