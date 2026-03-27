<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpHelper
{
    public static function checkOrder($orderId)
    {
        $token = config('services.5sim.token');
        $url = "https://5sim.net/v1/user/check/{$orderId}";
        $resp = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])->get($url);

        // If SMS/OTP is received, update transaction status to completed
        if ($resp->ok()) {
            $data = $resp->json();
            
            // Check if SMS has been received
            if (!empty($data['sms']) && is_array($data['sms'])) {
                $smsData = $data['sms'][0] ?? null;
                if ($smsData && !empty($smsData['code'])) {
                    $otpCode = $smsData['code'];
                    // Update transaction in DB using request_id with 'completed' status
                    \App\Models\Transaction::where('request_id', $orderId)
                        ->update([
                            'sms_code' => $otpCode,
                            'status' => 'completed',
                        ]);
                    Log::info("OTP code saved for order {$orderId}: {$otpCode}");
                }
            }
        }
        return $resp;
    }

    public static function cancelOrder($orderId)
    {
        $token = config('services.5sim.token');
        $url = "https://5sim.net/v1/user/cancel/{$orderId}";
        $resp = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])->get($url);
        return $resp;
    }

    public static function rebuyNumber($product, $number)
    {
        $token = config('services.5sim.token');
        $url = "https://5sim.net/v1/user/reuse/{$product}/{$number}";
        $resp = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])->get($url);
        return $resp;
    }
}
