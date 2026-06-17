<?php

namespace App\Http\Controllers;

use App\Helpers\DemmyPayHelper;
use App\Helpers\PinHelper;
use App\Helpers\ReferralBalanceHelper;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AirtimeController extends Controller
{
    public function show()
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        return view('users.airtime', compact('wallet'));
    }

    private function getAirtimeProductId($code): ?int
    {
        return match ($code) {
            '01' => 1,
            '02' => 2,
            '03' => 3,
            '04' => 4,
            default => null,
        };
    }

    public function recharge(Request $request)
    {
        $validated = $request->validate([
            'amount'               => 'required|numeric|min:50|max:50000',
            'network'              => 'required|string|in:01,02,03,04',
            'recipient'            => 'required|string|min:8|max:15',
            'pin'                  => 'required|string|size:4',
            'use_referral_balance' => 'sometimes|boolean',
        ]);

        try {
            // ── Verify PIN ────────────────────────────────────────────────────
            PinHelper::verifyPin($validated['pin']);

            $amount    = (float) $validated['amount'];
            $network   = $validated['network'];
            $recipient = preg_replace('/\D/', '', $validated['recipient']);

            if (strlen($recipient) < 8 || strlen($recipient) > 15) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid recipient phone number.',
                ], 400);
            }

            $productId = $this->getAirtimeProductId($network);
            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid network code provided.',
                ], 400);
            }

            // ── Load user and wallet ──────────────────────────────────────────
            /** @var User $user */
            $user   = Auth::user();
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your wallet could not be accessed. Please contact support.',
                ], 500);
            }

            // ── Referral balance split ────────────────────────────────────────
            $useReferral = !empty($validated['use_referral_balance'])
                && ReferralBalanceHelper::isEligible($user);

            if ($useReferral) {
                $split = ReferralBalanceHelper::calculate($user, $wallet, $amount);
            } else {
                $split = [
                    'referral_deduction' => 0,
                    'wallet_deduction'   => $amount,
                    'can_proceed'        => true,
                    'shortfall'          => 0,
                ];
            }

            // ── Balance check ─────────────────────────────────────────────────
            if ((float) $wallet->balance < $split['wallet_deduction']) {
                $shortfall = round($split['wallet_deduction'] - $wallet->balance, 2);

                return response()->json([
                    'success'    => false,
                    'message'    => 'Insufficient wallet balance.'
                        . ($split['referral_deduction'] > 0
                            ? ' Your referral balance will cover ₦' . number_format($split['referral_deduction'], 2) . ','
                              . ' but the wallet still needs ₦' . number_format($split['wallet_deduction'], 2) . '.'
                            : ' Required: ₦' . number_format($amount, 2) . '.')
                        . ' Shortfall: ₦' . number_format($shortfall, 2) . '. Please fund your wallet.',
                    'error_type' => 'insufficient_balance',
                    'details'    => [
                        'Required'             => '₦' . number_format($amount, 2),
                        'Covered by referral'  => '₦' . number_format($split['referral_deduction'], 2),
                        'Required from wallet' => '₦' . number_format($split['wallet_deduction'], 2),
                        'Wallet balance'       => '₦' . number_format($wallet->balance, 2),
                        'Shortfall'            => '₦' . number_format($shortfall, 2),
                    ],
                ], 400);
            }

            // ── Generate reference ────────────────────────────────────────────
            $customerReference = 'airtime_' . strtolower(Str::random(10) . '_' . uniqid());

            DB::beginTransaction();

            // ── Call provider API ─────────────────────────────────────────────
            $response = DemmyPayHelper::buyAirtime(
                $amount,
                $productId,
                $recipient,
                $customerReference
            );

            $networkNames = [
                '01' => 'MTN',
                '02' => 'Glo',
                '03' => 'Airtel',
                '04' => '9mobile',
            ];
            $networkName = $networkNames[$network] ?? 'Unknown';

            $apiSuccess = isset($response['status']) && (int) $response['status'] === 200;

            // ── Save transaction record ───────────────────────────────────────
            $transaction = Transaction::create([
                'reference'          => substr($customerReference, 0, 100),
                'user_id'            => $user->id,
                'type'               => 'airtime',
                'channel'            => 'DemmyPay',
                'provider'           => $networkName,
                'phone'              => $recipient,
                'amount'             => $amount,
                'status'             => $apiSuccess ? 'success' : 'failed',
                'response'           => json_encode(array_merge($response, [
                    'referral_deducted' => $split['referral_deduction'],
                    'wallet_deducted'   => $split['wallet_deduction'],
                ])),
            ]);

            if ($apiSuccess) {
                // ── Deduct balances (referral first, then wallet) ─────────────
                $deducted = ReferralBalanceHelper::apply($user, $wallet, $amount);

                DB::commit();

                $successMessage = "Your {$networkName} airtime recharge of ₦"
                    . number_format($amount, 2)
                    . " to {$recipient} was successful!";

                if ($deducted['referral_deduction'] > 0) {
                    $successMessage .= ' ₦' . number_format($deducted['referral_deduction'], 2)
                        . ' was paid from your referral balance.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data'    => [
                        'transaction_reference' => $transaction->reference,
                        'network'               => $networkName,
                        'network_code'          => $network,
                        'recipient'             => $recipient,
                        'amount'                => $amount,
                        'formatted_amount'      => '₦' . number_format($amount, 2),
                        'referral_deducted'     => $deducted['referral_deduction'],
                        'wallet_deducted'       => $deducted['wallet_deduction'],
                        'new_balance'           => $wallet->fresh()->balance,
                        'formatted_balance'     => '₦' . number_format($wallet->fresh()->balance, 2),
                        'transaction_time'      => now()->format('Y-m-d H:i:s'),
                        'provider_response'     => $response['data'] ?? null,
                    ],
                    'transaction_id' => $transaction->id,
                ]);
            }

            // ── API failed — do not deduct ────────────────────────────────────
            DB::rollBack();

            $errorMessage = $response['detail']
                ?? $response['message']
                ?? $response['error']
                ?? 'Airtime purchase failed. Your funds were not deducted.';

            return response()->json([
                'success'           => false,
                'message'           => $errorMessage,
                'error_code'        => $response['status'] ?? 'unknown',
                'provider_response' => $response,
            ], 400);

        } catch (ValidationException $e) {
            DB::rollBack();

            $pinError = $e->validator->errors()->first('pin');
            if ($pinError) {
                return response()->json([
                    'success'    => false,
                    'message'    => $pinError,
                    'error_type' => 'pin_validation',
                ], 422);
            }

            return response()->json([
                'success'    => false,
                'message'    => 'Validation failed. Please check your input.',
                'error_type' => 'validation',
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Airtime purchase error', [
                'user_id'   => auth()->id(),
                'error'     => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            return response()->json([
                'success'    => false,
                'message'    => 'An error occurred while processing your request. Your funds were not deducted.',
                'error_type' => 'system_error',
            ], 500);
        }
    }

    public function renderSuccessPartial(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'details' => 'sometimes|array',
        ]);

        return view('partials.success', [
            'title'   => $data['title'],
            'message' => $data['message'],
            'details' => $data['details'] ?? [],
        ])->render();
    }
}