<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $latestTransactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(4)
            ->get();
        $services = smm_api()->services();

    // Group services by category
    $categories = collect($services)->groupBy('category')->map(function ($services) {
        return $services->map(function ($service) {
            return (object) [
                'id'   => $service->service,   // access as object
                'name' => $service->name,
                // Apply +5% markup
                'rate' => round($service->rate * 1.05, 2),
                'min'  => (int) $service->min,
                'max'  => (int) $service->max,
                'desc' => $service->desc ?? '',
            ];
        });
    });

        $wallet = $user->wallet()->with('virtualAccounts')->first();

        return view('users.home', compact('latestTransactions', 'wallet', 'categories'));
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

    public function profile()
    {
        $user = auth()->user();

        $wallet = $user->wallet()->with('virtualAccounts')->first();

        return view('users.profile', compact('wallet'));
    }

    public function updatePin(Request $request)
    {
        $request->validate([
            'pin' => ['required', 'digits:4', 'confirmed'], // confirmed checks pin_confirmation
        ]);

        $user = auth()->user();
        $user->pin = Hash::make($request->pin); // hash pin
        $user->save();

        return redirect()->route('profile')
                         ->with('success', 'Your PIN has been updated successfully.');
    }


}
