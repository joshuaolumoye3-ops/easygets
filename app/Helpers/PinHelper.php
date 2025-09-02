<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinHelper
{
    /**
     * Verify if the given PIN matches the authenticated user's stored PIN.
     *
     * @param string $inputPin
     * @param \App\Models\User|null $user
     * @return bool
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function verifyPin(string $inputPin, $user = null): bool
    {
        $user = $user ?? auth()->user();

        // If no user or no pin stored, throw error
        if (!$user || !$user->pin) {
            throw ValidationException::withMessages([
                'pin' => 'You have not created a PIN. Please visit your profile to create a transaction PIN.',
            ]);
        }

        // Sanitize input: remove spaces and allow only digits
        $inputPin = preg_replace('/\D/', '', $inputPin);

        // Must be exactly 4 digits
        if (strlen($inputPin) !== 4) {
            throw ValidationException::withMessages([
                'pin' => 'PIN must be exactly 4 digits.',
            ]);
        }

        // Securely check hashed pin
        if (!Hash::check($inputPin, $user->pin)) {
            throw ValidationException::withMessages([
                'pin' => 'The PIN you entered is incorrect.',
            ]);
        }

        return true;
    }
}
