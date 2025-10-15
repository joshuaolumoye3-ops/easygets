<?php

namespace App\Helpers;

use App\Services\FlutterwaveService;
use Illuminate\Support\Facades\Log;

class FlutterwaveHelper
{
    public static function createUserVirtualAccount($user_data)
    {
        $user = $user_data['user'];
        $amount = $user_data['amount'] ?? null;
        try {
            $service = new FlutterwaveService();
            $response = $service->createVirtualAccount($user_data);

            Log::info('Flutterwave Helper Response', [
                'user_id' => $user->id,
                'response' => $response
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Flutterwave Helper Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public static function verifyTransaction($transactionId)
    {
        $service = new FlutterwaveService();
        return $service->verifyTransaction($transactionId);
    }
}
