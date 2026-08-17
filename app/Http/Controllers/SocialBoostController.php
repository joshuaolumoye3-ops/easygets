<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\PinHelper;
use App\Helpers\MoreThanPanelHelper;
use App\Helpers\FiveSmmHelper;
use App\Models\SocialServiceDescription;
use App\Models\Provider as ProviderModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SocialBoostController extends Controller
{
    protected $exchangeRate = 1500;

    /** slug => ['label' => ..., 'helper' => FQCN] */
    protected const PROVIDERS = [
        'morethanpanel' => ['label' => 'Provider 1', 'cache' => 'mtp_services.json', 'route' => 'provider1'],
        'fivesmm'       => ['label' => 'Provider 2', 'cache' => 'fivesmm_services.json', 'route' => 'provider2'],
    ];

    /** Internal slug → URL slug (e.g. 'morethanpanel' → 'provider1') */
    private function routeSlug(string $internal): string
    {
        return self::PROVIDERS[$internal]['route'] ?? $internal;
    }

    /** URL slug → internal slug (e.g. 'provider1' → 'morethanpanel') */
    private function internalSlug(?string $route): ?string
    {
        if (!$route) return null;
        foreach (self::PROVIDERS as $internal => $meta) {
            if ($meta['route'] === $route) return $internal;
        }
        return null;
    }

    protected function helperFor(string $slug)
    {
        return $slug === 'fivesmm' ? new FiveSmmHelper() : new MoreThanPanelHelper();
    }

    /**
     * Providers that are active in the DB AND known to this controller.
     */
    protected function activeProviders(): array
    {
        $rows = DB::table('providers')
            ->whereIn('api_identifier', array_keys(self::PROVIDERS))
            ->where('status', 'active')
            ->pluck('api_identifier')
            ->all();

        $out = [];
        foreach ($rows as $slug) {
            if (isset(self::PROVIDERS[$slug])) {
                $out[$slug] = self::PROVIDERS[$slug]['label'];
            }
        }
        return $out;
    }

    public function index(Request $request)
{
    $user   = Auth::user();
    $wallet = $user->wallet ?? null;

    $activeInternal = $this->activeProviders(); // [internal_slug => label], active-only

    // Route-slug-keyed list for the view (picker links, "Switch" label, etc.)
    $providers = [];
    foreach ($activeInternal as $internal => $label) {
        $providers[$this->routeSlug($internal)] = $label;
    }

    if (empty($providers)) {
        return view('users.social-boost', [
            'wallet' => $wallet,
            'groupedServices' => [],
            'exchangeRate' => $this->exchangeRate,
            'providers' => [],
            'selectedProvider' => null,
            'selectedProviderRoute' => null,
            'error' => 'No social boost providers are currently active. Please contact support.',
        ]);
    }

    $routeParam       = $request->query('provider'); // e.g. 'provider1'
    $selectedProvider = $this->internalSlug($routeParam); // translate → 'morethanpanel'

    if ($selectedProvider && !isset($activeInternal[$selectedProvider])) {
        $selectedProvider = null;
    }

    if (!$selectedProvider && count($activeInternal) === 1) {
        $selectedProvider = array_key_first($activeInternal);
    }

    $selectedProviderRoute = $selectedProvider ? $this->routeSlug($selectedProvider) : null;

    if (!$selectedProvider) {
        return view('users.social-boost', [
            'wallet' => $wallet,
            'groupedServices' => [],
            'exchangeRate' => $this->exchangeRate,
            'providers' => $providers,
            'selectedProvider' => null,
            'selectedProviderRoute' => null,
        ]);
    }

    try {
        $services = $this->getCachedServices($selectedProvider);

        if (!is_array($services) || empty($services)) {
            return view('users.social-boost', [
                'wallet' => $wallet,
                'groupedServices' => [],
                'exchangeRate' => $this->exchangeRate,
                'providers' => $providers,
                'selectedProvider' => $selectedProvider,
                'selectedProviderRoute' => $selectedProviderRoute,
                'error' => 'Unable to load services from ' . self::PROVIDERS[$selectedProvider]['label'] . '. Please try again later.',
            ]);
        }

        $groupedServices = $this->groupServices($services, $selectedProvider);

        return view('users.social-boost', [
            'wallet' => $wallet,
            'groupedServices' => $groupedServices,
            'exchangeRate' => $this->exchangeRate,
            'providers' => $providers,
            'selectedProvider' => $selectedProvider,
            'selectedProviderRoute' => $selectedProviderRoute,
        ]);
    } catch (\Exception $e) {
        Log::error('Social boost index error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

        return view('users.social-boost', [
            'wallet' => $wallet,
            'groupedServices' => [],
            'exchangeRate' => $this->exchangeRate,
            'providers' => $providers,
            'selectedProvider' => $selectedProvider,
            'selectedProviderRoute' => $selectedProviderRoute,
            'error' => 'An error occurred while loading services. Please try again.',
        ]);
    }
}

    private function getCachedServices(string $providerSlug)
    {
        $cacheFile = self::PROVIDERS[$providerSlug]['cache'];
        $cachePath = storage_path('app/cache/' . $cacheFile);
        $cacheAge  = 3600;

        try {
            if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $cacheAge) {
                $services = json_decode(file_get_contents($cachePath), true);
                if (is_array($services) && !empty($services)) return $services;
            }

            $services = $this->helperFor($providerSlug)->getServices();

            if (!is_array($services) || empty($services)) return [];

            $cacheDir = dirname($cachePath);
            if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
            file_put_contents($cachePath, json_encode($services));

            return $services;
        } catch (\Exception $e) {
            Log::error('getCachedServices failed', ['provider' => $providerSlug, 'error' => $e->getMessage()]);
            return $this->helperFor($providerSlug)->getServices() ?: [];
        }
    }

    public function purchase(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
            }
            $wallet = $user->wallet;

            $validated = $request->validate([
                'provider'             => 'required|string|in:' . implode(',', array_keys(self::PROVIDERS)),
                'service_id'           => 'required|integer',
                'link'                 => 'required|url|max:500',
                'quantity'             => 'required|integer|min:1',
                'pin'                  => 'required|digits:4',
                'use_referral_balance' => 'sometimes|boolean',
                'comments'             => 'nullable|string|max:5000',
                'username'             => 'nullable|string|max:100',
                'usernames'            => 'nullable|string|max:5000',
                'groups'               => 'nullable|string|max:8000',
                'media'                => 'nullable|url|max:1000',
                'keywords'             => 'nullable|string|max:1000',
                'hashtags'             => 'nullable|string|max:1000',
                'runs'                 => 'nullable|integer|min:1',
                'interval'             => 'nullable|integer|min:1',
                'answer_number'        => 'nullable|string|max:10',
                'posts'                => 'nullable|integer',
                'delay'                => 'nullable|integer',
                'expiry'               => 'nullable|string',
                'old_posts'            => 'nullable|integer',
            ]);

            $providerSlug = $validated['provider'];

            if (!isset($this->activeProviders()[$providerSlug])) {
                return response()->json(['success' => false, 'message' => 'This provider is currently unavailable.'], 422);
            }

            $helper = $this->helperFor($providerSlug);

            PinHelper::verifyPin($validated['pin']);

            $services = $this->getCachedServices($providerSlug);
            if (!is_array($services) || empty($services)) {
                return response()->json(['success' => false, 'message' => 'Unable to load service details. Please try again.'], 422);
            }

            $service = collect($services)->firstWhere('service', $validated['service_id']);
            if (!$service) {
                return response()->json(['success' => false, 'message' => 'Invalid service selected. Please refresh and try again.'], 422);
            }

            $validation = $helper->validateOrderParams($service, $validated['quantity']);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 422);
            }

            $adminPercentage = intval(
                SocialServiceDescription::where('provider', $providerSlug)
                    ->where('service_id', (string) $validated['service_id'])
                    ->value('admin_percentage') ?? 0
            );

            $costs     = $helper->calculateCost($service['rate'], $validated['quantity'], $this->exchangeRate, $service['service'] ?? null, $adminPercentage);
            $costLocal = $costs['cost_local'];
            $costUsd   = $costs['cost_usd'];

            $useReferral = !empty($validated['use_referral_balance'])
                && \App\Helpers\ReferralBalanceHelper::isEligible($user);

            $split = $useReferral
                ? \App\Helpers\ReferralBalanceHelper::calculate($user, $wallet, $costLocal)
                : ['referral_deduction' => 0, 'wallet_deduction' => $costLocal, 'can_proceed' => true, 'shortfall' => 0];

            if ((float) $wallet->balance < $split['wallet_deduction']) {
                $referralNote = $split['referral_deduction'] > 0
                    ? ' (₦' . number_format($split['referral_deduction'], 2) . ' covered by referral balance).'
                    : '.';

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Required from wallet: ₦' . number_format($split['wallet_deduction'], 2) . $referralNote,
                ], 422);
            }

            DB::beginTransaction();

            try {
                $orderData = [
                    'service'  => $validated['service_id'],
                    'link'     => $validated['link'],
                    'quantity' => $validated['quantity'],
                ];

                foreach (['comments','username','usernames','groups','media','keywords','hashtags','answer_number','expiry'] as $f) {
                    if (!empty($validated[$f])) $orderData[$f] = $validated[$f];
                }
                foreach (['runs','interval','posts','delay','old_posts'] as $p) {
                    if (isset($validated[$p]) && $validated[$p] !== null) $orderData[$p] = $validated[$p];
                }

                $orderResponse = $helper->placeOrder($orderData);

                if (!$orderResponse || isset($orderResponse['error'])) {
                    $errorMsg = $orderResponse['error'] ?? 'Failed to place order with provider';
                    Log::error('SMM Order Error', ['provider' => $providerSlug, 'response' => $orderResponse, 'order_data' => $orderData]);
                    throw new \Exception($errorMsg);
                }
                if (!isset($orderResponse['order'])) {
                    Log::error('SMM Invalid Response', ['provider' => $providerSlug, 'response' => $orderResponse]);
                    throw new \Exception('Invalid response from provider. Please try again.');
                }

                $deducted = \App\Helpers\ReferralBalanceHelper::apply($user, $wallet, $costLocal);

                $reference = 'SB-' . strtoupper(uniqid());
                $label     = self::PROVIDERS[$providerSlug]['label'];

                $transactionId = DB::table('transactions')->insertGetId([
                    'user_id'           => $user->id,
                    'reference'         => $reference,
                    'request_id'        => $orderResponse['order'],
                    'type'              => 'social_boost',
                    'channel'           => $label . ' Social Package',
                    'provider'          => $label . ' Social Package',
                    'service_id'        => $validated['service_id'],
                    'service_name'      => $service['name'],
                    'service_logo'      => $helper->getServiceLogo($service['category'], $service['name']),
                    'amount'            => $costLocal,
                    'original_cost_usd' => $costUsd,
                    'status'            => 'pending',
                    'description'       => "Social Boost ({$label}): {$service['name']} - " . number_format($validated['quantity']) . " units",
                    'response'          => json_encode([
                        'smm_provider'      => $providerSlug,     // ← used for status checks / history display
                        'provider_label'    => $label,
                        'link'              => $validated['link'],
                        'quantity'          => $validated['quantity'],
                        'service_type'      => $service['type'],
                        'category'          => $service['category'],
                        'comments'          => $validated['comments'] ?? null,
                        'username'          => $validated['username'] ?? null,
                        'usernames'         => $validated['usernames'] ?? null,
                        'groups'            => $validated['groups'] ?? null,
                        'media'             => $validated['media'] ?? null,
                        'keywords'          => $validated['keywords'] ?? null,
                        'hashtags'          => $validated['hashtags'] ?? null,
                        'min'               => $service['min'],
                        'max'               => $service['max'],
                        'rate_per_k'        => $service['rate'],
                        'refill'            => $service['refill'] ?? false,
                        'cancel'            => $service['cancel'] ?? false,
                        'referral_deducted' => $deducted['referral_deduction'],
                        'wallet_deducted'   => $deducted['wallet_deduction'],
                        'order_placed_at'   => now()->toIso8601String(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully! Your boost is being processed.',
                    'data'    => [
                        'transaction_id'        => $transactionId,
                        'transaction_reference' => $reference,
                        'mtp_order_id'          => $orderResponse['order'],
                        'service_name'          => $service['name'],
                        'provider_label'        => $label,
                        'quantity'              => number_format($validated['quantity']),
                        'link'                  => $validated['link'],
                        'formatted_amount'      => '₦' . number_format($costLocal, 2),
                        'formatted_balance'     => '₦' . number_format($wallet->balance, 2),
                        'referral_deducted'     => $deducted['referral_deduction'],
                        'wallet_deducted'       => $deducted['wallet_deduction'],
                        'transaction_time'      => now()->toIso8601String(),
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Social boost purchase failed: ' . $e->getMessage(), [
                    'user_id' => $user->id, 'provider' => $providerSlug, 'service_id' => $validated['service_id'],
                ]);
                return response()->json(['success' => false, 'message' => 'Failed to process order: ' . $e->getMessage()], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false, 'message' => 'Please check your input and try again.',
                'errors' => $e->errors(), 'error_type' => 'validation',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Social boost purchase error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function checkStatus(Request $request)
    {
        try {
            $request->validate(['transaction_id' => 'required|integer']);
            $user = Auth::user();

            $transaction = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('id', $request->transaction_id)
                ->where('type', 'social_boost')
                ->first();

            if (!$transaction) {
                return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
            }

            if (in_array($transaction->status, ['completed', 'cancelled', 'refunded'])) {
                return response()->json([
                    'success' => true,
                    'data' => ['status' => ucfirst($transaction->status), 'local_status' => $transaction->status, 'message' => 'Order already ' . $transaction->status],
                ]);
            }

            $responseData = json_decode($transaction->response, true) ?? [];
            $providerSlug = $responseData['smm_provider'] ?? 'morethanpanel'; // legacy rows default to MTP
            $helper       = $this->helperFor($providerSlug);

            $statusResponse = $helper->getOrderStatus($transaction->request_id);

            if (!$statusResponse) {
                return response()->json(['success' => false, 'message' => 'Unable to fetch order status from provider.', 'status' => 'pending']);
            }

            $localStatus = $helper->mapStatus($statusResponse['status'] ?? 'Pending');

            DB::table('transactions')->where('id', $transaction->id)->update([
                'status'   => $localStatus,
                'response' => json_encode(array_merge($responseData, [
                    'mtp_status'   => $statusResponse['status'] ?? null,
                    'start_count'  => $statusResponse['start_count'] ?? null,
                    'remains'      => $statusResponse['remains'] ?? null,
                    'charge'       => $statusResponse['charge'] ?? null,
                    'last_checked' => now()->toIso8601String(),
                ])),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'status'       => $statusResponse['status'],
                    'local_status' => $localStatus,
                    'charge'       => $statusResponse['charge'] ?? null,
                    'start_count'  => $statusResponse['start_count'] ?? null,
                    'remains'      => $statusResponse['remains'] ?? null,
                    'currency'     => $statusResponse['currency'] ?? 'USD',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Status check error: ' . $e->getMessage(), ['transaction_id' => $request->transaction_id ?? null]);
            return response()->json(['success' => false, 'message' => 'An error occurred while checking status.', 'status' => 'pending'], 500);
        }
    }

    private function groupServices($services, string $providerSlug)
    {
        $grouped = [];
        $skipped = 0;
        $helper  = $this->helperFor($providerSlug);

        $custom = SocialServiceDescription::where('provider', $providerSlug)->get()->keyBy('service_id');

        foreach ($services as $service) {
            if (empty($service['service']) || empty($service['name'])) {
                $skipped++;
                continue;
            }

            try {
                $formatted = $helper->formatServiceForDisplay($service, $this->exchangeRate);
                $formatted['provider'] = $providerSlug;

                $svcId = $formatted['service_id'];
                if (isset($custom[$svcId])) {
                    $record = $custom[$svcId];
                    if (!empty($record->description)) $formatted['description'] = $record->description;
                    $formatted['custom_name']      = $record->custom_name ?? null;
                    $formatted['custom_category']  = $record->custom_category ?? null;
                    $formatted['admin_percentage'] = intval($record->admin_percentage ?? 0);
                    if (!empty($record->custom_name)) $formatted['name'] = $record->custom_name;
                    if (!empty($record->custom_category)) $formatted['category'] = $record->custom_category;
                } else {
                    $formatted['custom_name']      = null;
                    $formatted['custom_category']  = null;
                    $formatted['admin_percentage'] = 0;
                }

                $category = !empty($formatted['category']) ? $formatted['category'] : 'Other';
                if (!isset($grouped[$category])) $grouped[$category] = [];

                $serviceCode = $service['service'] ?? null;
                $pricing     = $helper->getProductPricing($serviceCode);

                if (!empty($pricing) && isset($pricing['client_price']) && $pricing['client_price'] !== null) {
                    $clientPriceRaw     = $pricing['client_price'];
                    $providerPercentage = intval($pricing['provider_percentage'] ?? 0);
                    $finalPerUnit       = round($clientPriceRaw * (1 + ($providerPercentage / 100)), 2);
                    $basePerUnit        = $finalPerUnit;

                    $adminPct = intval($formatted['admin_percentage'] ?? 0);
                    if ($adminPct > 0) $basePerUnit = round($basePerUnit * (1 + $adminPct / 100), 2);

                    $formatted['client_price_raw']    = $clientPriceRaw;
                    $formatted['client_price']        = $basePerUnit;
                    $formatted['percentage_applied']  = $providerPercentage + $adminPct;
                    $formatted['pricing_source']      = 'product_database';
                    $formatted['rate_local_per_unit'] = round($basePerUnit, 4);
                    $formatted['rate_local']          = round($basePerUnit * 1000, 2);
                } else {
                    $formatted['pricing_source'] = 'api_rate';
                    $adminPct = intval($formatted['admin_percentage'] ?? 0);
                    $formatted['percentage_applied'] = $adminPct;

                    $basePerUnit = $formatted['rate_local_per_unit'] ?? (isset($formatted['rate_local']) ? $formatted['rate_local'] / 1000 : 0);
                    if ($adminPct > 0) $basePerUnit = round($basePerUnit * (1 + $adminPct / 100), 4);

                    $formatted['rate_local_per_unit'] = $basePerUnit;
                    $formatted['rate_local']          = round($basePerUnit * 1000, 2);
                }

                $grouped[$category][] = $formatted;
            } catch (\Exception $e) {
                Log::warning('Failed to format service', ['provider' => $providerSlug, 'service_id' => $service['service'] ?? 'unknown', 'error' => $e->getMessage()]);
                $skipped++;
            }
        }

        ksort($grouped);
        return $grouped;
    }

    // ═══════════ ADMIN ═══════════

    public function adminListServices(Request $request)
    {
        $providerSlug = $this->internalSlug($request->query('provider')) ?? 'morethanpanel';
        if (!isset(self::PROVIDERS[$providerSlug])) $providerSlug = 'morethanpanel';

        try {
            $services = $this->getCachedServices($providerSlug);
            $helper   = $this->helperFor($providerSlug);
            $custom   = SocialServiceDescription::where('provider', $providerSlug)->get()->keyBy('service_id');

            $formattedServices = [];
            foreach ($services as $service) {
                $formatted = $helper->formatServiceForDisplay($service, $this->exchangeRate);
                $svcId     = $formatted['service_id'];
                $formatted['description'] = $custom[$svcId]->description ?? null;

                if (isset($custom[$svcId])) {
                    $record = $custom[$svcId];
                    $formatted['custom_name']      = $record->custom_name ?? null;
                    $formatted['custom_category']  = $record->custom_category ?? null;
                    $formatted['admin_percentage'] = intval($record->admin_percentage ?? 0);
                    if (!empty($record->custom_name)) $formatted['name'] = $record->custom_name;
                    if (!empty($record->custom_category)) $formatted['category'] = $record->custom_category;
                } else {
                    $formatted['custom_name']      = null;
                    $formatted['custom_category']  = null;
                    $formatted['admin_percentage'] = 0;
                }

                $formattedServices[] = $formatted;
            }

            return view('admin.social-services', [
                'services'             => $formattedServices,
                'providers'            => self::PROVIDERS,
                'selectedProvider'     => $providerSlug,
                'selectedProviderRoute' => $this->routeSlug($providerSlug),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin list services error', ['error' => $e->getMessage()]);
            return view('admin.social-services', [
                'services' => [], 'providers' => self::PROVIDERS, 'selectedProvider' => $providerSlug,
                'selectedProviderRoute' => $this->routeSlug($providerSlug),
            ])->with('error', 'Failed to load services');
        }
    }

    public function adminRefreshServices(Request $request)
    {
        $providerSlug = $this->internalSlug($request->query('provider')) ?? 'morethanpanel';
        if (!isset(self::PROVIDERS[$providerSlug])) $providerSlug = 'morethanpanel';

        $routeSlug = $this->routeSlug($providerSlug);

        try {
            $cachePath = storage_path('app/cache/' . self::PROVIDERS[$providerSlug]['cache']);
            if (file_exists($cachePath)) unlink($cachePath);
            $this->getCachedServices($providerSlug);

            return redirect()->route('admin.social.services', ['provider' => $routeSlug])->with('success', 'Services refreshed successfully');
        } catch (\Exception $e) {
            Log::error('Admin refresh services error', ['error' => $e->getMessage()]);
            return redirect()->route('admin.social.services', ['provider' => $routeSlug])->with('error', 'Failed to refresh services');
        }
    }

    public function adminUpdateDescription(Request $request)
    {
        try {
            $validated = $request->validate([
                'provider'         => 'required|string|in:' . implode(',', array_keys(self::PROVIDERS)),
                'service_id'       => 'required|string',
                'description'      => 'nullable|string',
                'custom_name'      => 'nullable|string|max:255',
                'custom_category'  => 'nullable|string|max:255',
                'admin_percentage' => 'nullable|numeric|min:0',
            ]);

            SocialServiceDescription::updateOrCreate(
                ['provider' => $validated['provider'], 'service_id' => $validated['service_id']],
                [
                    'description'      => $validated['description'] ?? null,
                    'custom_name'      => $validated['custom_name'] ?? null,
                    'custom_category'  => $validated['custom_category'] ?? null,
                    'admin_percentage' => intval($validated['admin_percentage'] ?? 0),
                ]
            );

            $cachePath = storage_path('app/cache/' . self::PROVIDERS[$validated['provider']]['cache']);
            if (file_exists($cachePath)) unlink($cachePath);

            return redirect()->route('admin.social.services', ['provider' => $this->routeSlug($validated['provider'])])->with('success', 'Description updated successfully');
        } catch (\Exception $e) {
            Log::error('Admin update description error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to update description');
        }
    }

    public function adminBulkUpdatePercentage(Request $request)
    {
        $validated = $request->validate([
            'provider'           => 'required|string|in:' . implode(',', array_keys(self::PROVIDERS)),
            'global_percentage'  => 'required|numeric|min:0|max:1000',
        ]);

        $providerSlug = $validated['provider'];
        $percentage   = intval($validated['global_percentage']);

        try {
            DB::beginTransaction();

            SocialServiceDescription::where('provider', $providerSlug)->update(['admin_percentage' => $percentage]);

            $services = $this->getCachedServices($providerSlug);

            if (!empty($services)) {
                $allApiIds = collect($services)->pluck('service')->filter()->map(fn($id) => (string) $id)->unique()->values()->all();

                $existingIds = SocialServiceDescription::where('provider', $providerSlug)
                    ->whereIn('service_id', $allApiIds)->pluck('service_id')->all();

                $missingIds = array_diff($allApiIds, $existingIds);
                $now = now()->toDateTimeString();

                foreach (array_chunk($missingIds, 500) as $chunk) {
                    $rows = array_map(fn($id) => [
                        'service_id' => $id, 'provider' => $providerSlug, 'admin_percentage' => $percentage,
                        'description' => null, 'custom_name' => null, 'custom_category' => null,
                        'created_at' => $now, 'updated_at' => $now,
                    ], $chunk);
                    DB::table('social_service_descriptions')->insert($rows);
                }
            }

            DB::commit();

            $cachePath = storage_path('app/cache/' . self::PROVIDERS[$providerSlug]['cache']);
            if (file_exists($cachePath)) unlink($cachePath);

            $totalUpdated = SocialServiceDescription::where('provider', $providerSlug)->count();

            return redirect()->route('admin.social.services', ['provider' => $this->routeSlug($providerSlug)])
                ->with('success', "Global markup set to {$percentage}% for " . self::PROVIDERS[$providerSlug]['label'] . ". {$totalUpdated} services updated.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk percentage update failed', ['error' => $e->getMessage()]);
            return redirect()->route('admin.social.services', ['provider' => $this->routeSlug($providerSlug)])->with('error', 'Failed to apply global markup: ' . $e->getMessage());
        }
    }

    public function adminBulkReplaceNames(Request $request)
    {
        $validated = $request->validate([
            'provider'     => 'required|string|in:' . implode(',', array_keys(self::PROVIDERS)),
            'search_term'  => 'required|string|max:255',
            'replace_with' => 'required|string|max:255',
        ]);

        $providerSlug = $validated['provider'];
        $search = $validated['search_term'];
        $replace = $validated['replace_with'];

        try {
            $services = $this->getCachedServices($providerSlug);
            $helper = $this->helperFor($providerSlug);

            $rows = [];
            $now = now()->toDateTimeString();
            $matches = 0;

            foreach ($services as $service) {
                $formatted = $helper->formatServiceForDisplay($service, $this->exchangeRate);
                $name = $formatted['name'] ?? '';
                if ($name === null) continue;
                if (stripos($name, $search) !== false) {
                    $svcId = (string)($formatted['service_id'] ?? ($service['service'] ?? ''));
                    if (!$svcId) continue;
                    $newName = str_ireplace($search, $replace, $name);
                    // Ensure values fit DB column sizes (varchar 255)
                    $safeName = \Illuminate\Support\Str::limit($newName, 255, '');
                    $safeCategory = isset($formatted['category']) ? \Illuminate\Support\Str::limit($formatted['category'], 255, '') : null;

                    $rows[] = [
                        'service_id' => $svcId,
                        'provider' => $providerSlug,
                        'custom_name' => $safeName,
                        'description' => $formatted['description'] ?? null,
                        'custom_category' => $safeCategory,
                        'admin_percentage' => intval($formatted['admin_percentage'] ?? 0),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $matches++;
                }
            }

            if (empty($rows)) {
                return redirect()->route('admin.social.services', ['provider' => $this->routeSlug($providerSlug)])->with('success', 'No matching services found for "' . $search . '".');
            }

            // Upsert in chunks to handle large volumes
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('social_service_descriptions')->upsert(
                    $chunk,
                    ['provider','service_id'],
                    ['custom_name','description','custom_category','admin_percentage','updated_at']
                );
            }

            // Clear provider cache
            $cachePath = storage_path('app/cache/' . self::PROVIDERS[$providerSlug]['cache']);
            if (file_exists($cachePath)) unlink($cachePath);

            return redirect()->route('admin.social.services', ['provider' => $this->routeSlug($providerSlug)])->with('success', "Replaced occurrences across {$matches} services.");
        } catch (\Exception $e) {
            Log::error('Bulk replace names failed', ['error' => $e->getMessage()]);
            return redirect()->route('admin.social.services', ['provider' => $this->routeSlug($providerSlug)])->with('error', 'Failed to perform replace: ' . $e->getMessage());
        }
    }

    public function adminBulkReplaceStart(Request $request)
    {
        $validated = $request->validate([
            'provider'     => 'required|string|in:' . implode(',', array_keys(self::PROVIDERS)),
            'search_term'  => 'required|string|max:255',
            'replace_with' => 'required|string|max:255',
        ]);

        $providerSlug = $validated['provider'];
        $search = $validated['search_term'];
        $replace = $validated['replace_with'];

        try {
            $services = $this->getCachedServices($providerSlug);
            $helper = $this->helperFor($providerSlug);

            $rows = [];
            $now = now()->toDateTimeString();

            foreach ($services as $service) {
                $formatted = $helper->formatServiceForDisplay($service, $this->exchangeRate);
                $name = $formatted['name'] ?? '';
                if ($name === null) continue;
                if (stripos($name, $search) !== false) {
                    $svcId = (string)($formatted['service_id'] ?? ($service['service'] ?? ''));
                    if (!$svcId) continue;
                    $newName = str_ireplace($search, $replace, $name);

                    // Truncate safely by bytes to avoid MySQL errors
                    $safeName = mb_strcut($newName, 0, 191, 'UTF-8');
                    $safeCategory = isset($formatted['category']) ? mb_strcut($formatted['category'], 0, 191, 'UTF-8') : null;

                    $rows[] = [
                        'service_id' => $svcId,
                        'provider' => $providerSlug,
                        'custom_name' => $safeName,
                        'description' => $formatted['description'] ?? null,
                        'custom_category' => $safeCategory,
                        'admin_percentage' => intval($formatted['admin_percentage'] ?? 0),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $total = count($rows);
            if ($total === 0) {
                return response()->json(['success' => true, 'total' => 0, 'jobId' => null]);
            }

            $jobId = 'replace_job_' . Str::random(8) . '_' . time();
            Cache::put($jobId, $rows, 3600);

            return response()->json(['success' => true, 'total' => $total, 'jobId' => $jobId]);
        } catch (\Exception $e) {
            Log::error('Bulk replace start failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to start replace job: ' . $e->getMessage()], 500);
        }
    }

    public function adminBulkReplaceProcess(Request $request)
    {
        $validated = $request->validate([
            'jobId' => 'required|string',
            'chunk' => 'nullable|integer|min:1|max:1000',
        ]);

        $jobId = $validated['jobId'];
        $chunkSize = $validated['chunk'] ?? 100;

        $rows = Cache::get($jobId, []);
        if (empty($rows)) {
            return response()->json(['success' => true, 'done' => true, 'processed' => 0, 'remaining' => 0]);
        }

        $toProcess = array_splice($rows, 0, $chunkSize);
        try {
            DB::beginTransaction();
            $inserts = [];
            $processedCount = 0;

            foreach ($toProcess as $r) {
                // normalize service_id
                $svc = isset($r['service_id']) ? trim((string)$r['service_id']) : null;
                if (!$svc) continue;
                $r['service_id'] = $svc;

                $updateData = [
                    'custom_name' => $r['custom_name'],
                    'description' => $r['description'],
                    'custom_category' => $r['custom_category'],
                    'admin_percentage' => $r['admin_percentage'],
                    'updated_at' => $r['updated_at'],
                ];

                $affected = DB::table('social_service_descriptions')
                    ->where('provider', $r['provider'])
                    ->where('service_id', $r['service_id'])
                    ->update($updateData);

                if ($affected === 0) {
                    // prepare for insert (created_at present)
                    $inserts[] = $r;
                }
                $processedCount++;
            }

            if (!empty($inserts)) {
                // insert remaining new rows in one batch
                DB::table('social_service_descriptions')->insert($inserts);
            }

            DB::commit();

            $remaining = count($rows);
            Cache::put($jobId, $rows, 3600);

            if ($remaining === 0) {
                Cache::forget($jobId);
                return response()->json(['success' => true, 'done' => true, 'processed' => $processedCount, 'remaining' => 0]);
            }

            return response()->json(['success' => true, 'done' => false, 'processed' => $processedCount, 'remaining' => $remaining]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk replace process failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Chunk process failed: ' . $e->getMessage()], 500);
        }
    }
}