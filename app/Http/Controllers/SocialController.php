<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
   use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\Transaction;
// use App\Helpers\morethanpanel_helper;
use App\Helpers\morethanpanel_helper;



class SocialController extends Controller
{
public function services()
{

    $services = smm_api()->services();
    $categories = collect($services)->groupBy('category')->map(function ($services) {
        return $services->map(function ($service) {
            return (object) [
                'id'   => $service->service,   // access as object
                'name' => $service->name,
                'rate' => round($service->rate),
                'min'  => (int) $service->min,
                'max'  => (int) $service->max,
                'desc' => $service->desc ?? '',
            ];
        });

    });
 // dd($categories); // removed for production


    return view('users.socialPackage', compact('categories'));
}


public function order(Request $request)
{
    $request->validate([
        'service_id' => 'required|integer',
        'link'       => 'required|url',
        'quantity'   => 'required|integer|min:10',
        'pin'        => 'required|digits:4',
    ]);

    DB::beginTransaction();

    try {
        // Verify PIN first
        \App\Helpers\PinHelper::verifyPin($request->pin);

        // Find service — API returns objects with ->service not ->id
        $allServices = smm_api()->services();
        $service = collect($allServices)
            ->first(fn($s) => (int)($s->service ?? $s->id ?? 0) === (int)$request->service_id);

        if (!$service) {
            throw new \Exception('Invalid service selected.');
        }

        $serviceId   = $service->service ?? $service->id;
        $serviceName = $service->name;
        $rate        = (float)$service->rate;

        // ✅ FIX: Use MoreThanPanelHelper::calculateCost() so the amount charged
        // matches exactly what was shown to the user (admin client_price + markup percentage).
        $mtpHelper    = new \App\Helpers\MoreThanPanelHelper();
        $exchangeRate = 1500; // same default used in formatServiceForDisplay()
        $costBreakdown = $mtpHelper->calculateCost($rate, $request->quantity, $exchangeRate, (string)$serviceId);
        $amount = $costBreakdown['cost_local'];   // already rounded, includes admin markup

        // Lock wallet
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $amount) {
            throw new \Exception('Insufficient wallet balance. Required: ₦' . number_format($amount, 2));
        }

        // Place order BEFORE deducting — only deduct if order succeeds
        $response = smm_api()->order([
            'service'  => $serviceId,
            'link'     => $request->link,
            'quantity' => $request->quantity,
        ]);

        if (!isset($response->order)) {
            throw new \Exception($response->error ?? 'Order failed. Please try again.');
        }

        $mtpOrderId = $response->order;

        // Deduct wallet only after confirmed order
        $wallet->decrement('balance', $amount);

        // Determine category/logo from service name
        $category = $this->guessCategoryFromName($serviceName);
        $logo     = $this->getLogoFromCategory($category, $serviceName);

        Transaction::create([
            'reference'    => 'SMM-' . strtoupper(uniqid()),
            'user_id'      => auth()->id(),
            'type'         => 'social_boost',
            'channel'      => 'MoreThanPanel',
            'provider'     => 'MoreThanPanel',
            'service_id'   => $serviceId,
            'service_name' => $serviceName,
            'service_logo' => $logo,
            'account'      => $request->link,
            'amount'       => $amount,
            'status'       => 'pending',
            'request_id'   => $mtpOrderId,
            'description'  => "Social Boost: {$serviceName} — " . number_format($request->quantity) . " units",
            'response'     => json_encode([
                'link'             => $request->link,
                'quantity'         => $request->quantity,
                'service_type'     => $service->type ?? 'Default',
                'category'         => $category,
                'refill'           => $service->refill ?? false,
                'cancel'           => $service->cancel ?? false,
                'mtp_order_id'     => $mtpOrderId,
                'cost_source'      => $costBreakdown['source'],         // 'product_client_price' or 'api_rate_with_exchange'
                'percentage_applied' => $costBreakdown['percentage_applied'],
                'order_placed_at'  => now()->toIso8601String(),
            ]),
        ]);

        DB::commit();

        return back()->with('success', "Order placed! Order ID: {$mtpOrderId}. Track progress in your History.");

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return back()->with('error', $e->validator->errors()->first())->withInput();
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage())->withInput();
    }
}

private function guessCategoryFromName(string $name): string
{
    $name = strtolower($name);
    $map  = [
        'instagram' => 'Instagram',
        'facebook'  => 'Facebook',
        'tiktok'    => 'TikTok',
        'youtube'   => 'YouTube',
        'twitter'   => 'Twitter',
        'telegram'  => 'Telegram',
        'spotify'   => 'Spotify',
        'linkedin'  => 'LinkedIn',
        'snapchat'  => 'Snapchat',
        'twitch'    => 'Twitch',
        'discord'   => 'Discord',
        'threads'   => 'Threads',
        'pinterest' => 'Pinterest',
    ];
    foreach ($map as $key => $label) {
        if (str_contains($name, $key)) return $label;
    }
    return 'Social Media';
}

private function getLogoFromCategory(string $category, string $name = ''): string
{
    $logos = [
        'Instagram' => '📸', 'Facebook'  => '📘', 'TikTok'    => '🎵',
        'YouTube'   => '▶️', 'Twitter'   => '🐦', 'Telegram'  => '✈️',
        'Spotify'   => '🎧', 'LinkedIn'  => '💼', 'Snapchat'  => '👻',
        'Twitch'    => '🎮', 'Discord'   => '💬', 'Threads'   => '🧵',
        'Pinterest' => '📌',
    ];
    return $logos[$category] ?? '🚀';
}


}
