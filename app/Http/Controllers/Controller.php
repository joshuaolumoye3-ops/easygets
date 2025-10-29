<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Controller
{

    public function dashboard()
    {
        $user = auth()->user();
        $admin_rate_product = Product::select('client_price')
            ->where('product_code', 'morethanpanel')
            ->where('is_active', true)
            ->first();
        $admin_rate = $admin_rate_product->client_price;

        $latestTransactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(4)
            ->get();

        $services = smm_api()->services();

        $categories = collect($services)->groupBy('category')->map(function ($services) {
            return $services->map(function ($service) {
                return (object) [
                    'id'   => $service->service,
                    'name' => $service->name,
                    'rate' => round($service->rate * 1.05, 2),
                    'min'  => (int) $service->min,
                    'max'  => (int) $service->max,
                    'desc' => $service->desc ?? '',
                ];
            });
        });

        // Fetch SMS services for Quick Pick
        $token = env('SMSBUS_TOKEN');
        $cacheKey = 'smsbus_services';
        
        $smsServices = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($token) {
            try {
                $response = Http::timeout(30)
                    ->withOptions(['verify' => false])
                    ->get("https://sms-bus.com/api/control/list/projects", [
                        'token' => $token
                    ]);

                if ($response->successful()) {
                    $data = $response->json('data');
                    if (is_array($data) && !empty($data)) {
                        return collect($data)->values();
                    }
                }
            } catch (\Exception $e) {
                Log ::error('SMS-Bus API Error in dashboard', ['error' => $e->getMessage()]);
            }
            
            return collect([]);
        });

        // Map Quick Pick services
        $quickPickServices = [
            'facebook' => $smsServices->firstWhere('code', 'fb'),
            'whatsapp' => $smsServices->firstWhere('code', 'wa'),
            'telegram' => $smsServices->firstWhere('code', 'tg'),
            'google' => $smsServices->firstWhere('code', 'google'),
        ];

        $wallet = $user->wallet()->with('virtualAccounts')->first();

        return view('users.home', compact('latestTransactions', 'wallet', 'categories', 'admin_rate', 'quickPickServices'));
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
