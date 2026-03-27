<?php

namespace App\Helpers;

use App\Services\FlutterwaveService;
use Illuminate\Support\Facades\Log;

class FlutterwaveHelper
{
    /**
     * Initialize payment for user
     */
    public static function initializePayment($user_data)
    {
        $user = $user_data['user'];
        $amount = $user_data['amount'];

        try {
            $service = new FlutterwaveService();
            $response = $service->initializePayment($user_data);

            Log::info('Flutterwave Helper Payment Init', [
                'user_id' => $user->id,
                'amount' => $amount,
                'response' => $response
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Flutterwave Helper Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify transaction
     */
    public static function verifyTransaction($transactionId)
    {
        $service = new FlutterwaveService();
        return $service->verifyTransaction($transactionId);
    }
}