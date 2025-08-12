<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $latestTransactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(4)
            ->get();

        $wallet = $user->wallet()->with('virtualAccounts')->first();

        return view('users.home', compact('latestTransactions', 'wallet'));
    }

    public function transaction()
{
    $user = Auth::user();

    // Get transactions and group by date (Y-m-d)
    $transactions = Transaction::where('user_id', $user->id)
        ->latest()
        ->get()
        ->groupBy(fn ($txn) => Carbon::parse($txn->created_at)->format('d M, Y'));

    return view('users.transaction', compact('transactions'));
}
}
