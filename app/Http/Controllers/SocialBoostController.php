<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\PinHelper;
use App\Helpers\MoreThanPanelHelper;
use App\Models\SocialServiceDescription;

class SocialBoostController extends Controller
{
    protected $mtpHelper;
    protected $exchangeRate = 1500; // NGN to USD rate

    public function __construct()
    {
        $this->mtpHelper = new MoreThanPanelHelper();
    }

    /**
     * Show social boost purchase page
     */


    public function index()
    {
        $errorDetails = '';
        
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }
            
            $wallet = $user->wallet;
            
            // Check if API key is configured
            $apiKey = config('services.morethanpanel.api_key') ?? env('MORETHANPANEL_API_KEY');
            if (empty($apiKey)) {
                Log::error('MoreThanPanel API key not configured');
                $errorDetails = 'API key not configured. Please add MORETHANPANEL_API_KEY to your .env file.';
                
                return view('users.social-boost', [
                    'wallet' => $wallet,
                    'groupedServices' => [],
                    'exchangeRate' => $this->exchangeRate,
                    'error' => 'API configuration error. Please contact support.'
                ]);
            }
            
            Log::info('Starting service fetch...', ['api_key_exists' => !empty($apiKey)]);
            
            // Get services from MoreThanPanel API - Use file cache to avoid database issues
            $services = $this->getCachedServices();
            
            Log::info('Services fetched', [
                'is_array' => is_array($services),
                'count' => is_array($services) ? count($services) : 0,
                'type' => gettype($services)
            ]);

            // Better validation of services response
            if (!is_array($services)) {
                Log::error('Services is not an array', [
                    'type' => gettype($services),
                    'value' => $services
                ]);
                $errorDetails = 'Invalid API response format. Expected array, got ' . gettype($services);
                
                return view('users.social-boost', [
                    'wallet' => $wallet,
                    'groupedServices' => [],
                    'exchangeRate' => $this->exchangeRate,
                    'error' => 'Unable to load services from provider. Please contact support.'
                ]);
            }
            
            if (empty($services)) {
                Log::error('Services array is empty');
                $errorDetails = 'API returned empty services array';
                
                return view('users.social-boost', [
                    'wallet' => $wallet,
                    'groupedServices' => [],
                    'exchangeRate' => $this->exchangeRate,
                    'error' => 'No services available from provider. Please try again later.'
                ]);
            }

            // Group services by category
            Log::info('Grouping services...', ['service_count' => count($services)]);
            $groupedServices = $this->groupServices($services);
            
            if (empty($groupedServices)) {
                Log::warning('No services after grouping', [
                    'original_count' => count($services),
                    'first_service' => isset($services[0]) ? $services[0] : null
                ]);
                $errorDetails = 'Services could not be grouped. Check service data structure.';
                
                return view('users.social-boost', [
                    'wallet' => $wallet,
                    'groupedServices' => [],
                    'exchangeRate' => $this->exchangeRate,
                    'error' => 'No valid services available at the moment.'
                ]);
            }

            Log::info('Services loaded successfully', [
                'categories' => count($groupedServices),
                'total_services' => array_sum(array_map('count', $groupedServices)),
                'category_names' => array_keys($groupedServices)
            ]);

            $exchangeRate = $this->exchangeRate;

            // ✅ FIXED: Consistent view path
            return view('users.social-boost', compact('wallet', 'groupedServices', 'exchangeRate'));

        } catch (\Exception $e) {
            Log::error('Social boost index error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show detailed error in development
            $isDev = false; // force production mode
            $errorMessage = $isDev 
                ? 'Error: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')' 
                : 'An error occurred while loading services. Please try again.';
            
            return view('users.social-boost', [
                'wallet' => Auth::user()->wallet ?? null,
                'groupedServices' => [],
                'exchangeRate' => $this->exchangeRate,
                'error' => $errorMessage
            ]);
        }
    }

    /**
     * Get cached services using file cache
     */
    private function getCachedServices()
    {
        try {
            $cachePath = storage_path('app/cache/mtp_services.json');
            $cacheAge = 3600; // 1 hour
            
            // Check if cache file exists and is fresh
            if (file_exists($cachePath)) {
                $fileAge = time() - filemtime($cachePath);
                Log::info('Cache file found', [
                    'age_seconds' => $fileAge,
                    'is_fresh' => $fileAge < $cacheAge
                ]);
                
                if ($fileAge < $cacheAge) {
                    $cachedData = file_get_contents($cachePath);
                    $services = json_decode($cachedData, true);
                    
                    if (is_array($services) && !empty($services)) {
                        Log::info('Loaded from cache', ['count' => count($services)]);
                        return $services;
                    } else {
                        Log::warning('Cache file invalid', [
                            'json_error' => json_last_error_msg()
                        ]);
                    }
                }
            }
            
            // Fetch fresh data from API
            Log::info('Fetching fresh services from API...');
            $services = $this->mtpHelper->getServices();
            
            if (!is_array($services)) {
                Log::error('API returned non-array', ['type' => gettype($services)]);
                return [];
            }
            
            if (empty($services)) {
                Log::error('API returned empty array');
                return [];
            }
            
            // Save to file cache
            $cacheDir = dirname($cachePath);
            if (!is_dir($cacheDir)) {
                Log::info('Creating cache directory', ['path' => $cacheDir]);
                mkdir($cacheDir, 0755, true);
            }
            
            $jsonData = json_encode($services);
            if ($jsonData === false) {
                Log::error('Failed to encode services to JSON', [
                    'error' => json_last_error_msg()
                ]);
            } else {
                $written = file_put_contents($cachePath, $jsonData);
                Log::info('Services cached to file', [
                    'count' => count($services),
                    'bytes_written' => $written,
                    'path' => $cachePath
                ]);
            }
            
            return $services;
            
        } catch (\Exception $e) {
            Log::error('Cache operation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Try to get from API without caching
            return $this->mtpHelper->getServices() ?: [];
        }
    }

    /**
     * Process social boost purchase
     */
    public function purchase(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }
            
            $wallet = $user->wallet;

            // Validate request (allow commonly used extra params supported by MTP)
            $validated = $request->validate([
                'service_id' => 'required|integer',
                'link' => 'required|url|max:500',
                'quantity' => 'required|integer|min:1',
                'pin' => 'required|digits:4',
                'comments' => 'nullable|string|max:5000',
                'username' => 'nullable|string|max:100',
                'usernames' => 'nullable|string|max:5000',
                'groups' => 'nullable|string|max:8000',
                'media' => 'nullable|url|max:1000',
                'keywords' => 'nullable|string|max:1000',
                'hashtags' => 'nullable|string|max:1000',
                'runs' => 'nullable|integer|min:1',
                'interval' => 'nullable|integer|min:1',
                'answer_number' => 'nullable|string|max:10',
                'posts' => 'nullable|integer',
                'delay' => 'nullable|integer',
                'expiry' => 'nullable|string',
                'old_posts' => 'nullable|integer',
            ]);

            // Verify PIN
            PinHelper::verifyPin($validated['pin']);

            // Get service details from cache
            $services = $this->getCachedServices();
            
            if (!is_array($services) || empty($services)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to load service details. Please try again.',
                ], 422);
            }
            
            $service = collect($services)->firstWhere('service', $validated['service_id']);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid service selected. Please refresh and try again.',
                ], 422);
            }

            // Validate quantity range
            $validation = $this->mtpHelper->validateOrderParams($service, $validated['quantity']);
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                ], 422);
            }

            // Determine admin markup for this service, if configured
            $adminPercentage = intval(SocialServiceDescription::where('service_id', (string)$validated['service_id'])->value('admin_percentage') ?? 0);

            // Calculate cost (pass service id so product-level `client_price` can be applied)
            $costs = $this->mtpHelper->calculateCost(
                $service['rate'],
                $validated['quantity'], 
                $this->exchangeRate, 
                $service['service'] ?? null,
                $adminPercentage
            );
            $costLocal = $costs['cost_local'];
            $costUsd = $costs['cost_usd'];

            // Check wallet balance
            if ($wallet->balance < $costLocal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Required: ₦' . number_format($costLocal, 2) . '. Please fund your wallet.',
                ], 422);
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Prepare order data
                $orderData = [
                    'service' => $validated['service_id'],
                    'link' => $validated['link'],
                    'quantity' => $validated['quantity'],
                ];

                // Attach commonly used additional params if present
                if (!empty($validated['comments'])) {
                    $orderData['comments'] = $validated['comments'];
                }
                if (!empty($validated['username'])) {
                    $orderData['username'] = $validated['username'];
                }
                if (!empty($validated['usernames'])) {
                    $orderData['usernames'] = $validated['usernames'];
                }
                if (!empty($validated['groups'])) {
                    $orderData['groups'] = $validated['groups'];
                }
                if (!empty($validated['media'])) {
                    $orderData['media'] = $validated['media'];
                }
                if (!empty($validated['keywords'])) {
                    $orderData['keywords'] = $validated['keywords'];
                }
                if (!empty($validated['hashtags'])) {
                    $orderData['hashtags'] = $validated['hashtags'];
                }
                foreach (['runs','interval','posts','delay','old_posts'] as $p) {
                    if (isset($validated[$p]) && $validated[$p] !== null) {
                        $orderData[$p] = $validated[$p];
                    }
                }
                if (!empty($validated['answer_number'])) {
                    $orderData['answer_number'] = $validated['answer_number'];
                }
                if (!empty($validated['expiry'])) {
                    $orderData['expiry'] = $validated['expiry'];
                }

                // Place order with MoreThanPanel
                $orderResponse = $this->mtpHelper->placeOrder($orderData);

                if (!$orderResponse || isset($orderResponse['error'])) {
                    $errorMsg = $orderResponse['error'] ?? 'Failed to place order with provider';
                    Log::error('MTP Order Error', ['response' => $orderResponse, 'order_data' => $orderData]);
                    throw new \Exception($errorMsg);
                }
                
                if (!isset($orderResponse['order'])) {
                    Log::error('MTP Invalid Response', ['response' => $orderResponse]);
                    throw new \Exception('Invalid response from provider. Please try again.');
                }

                // Deduct from wallet AFTER successful order
                $wallet->balance -= $costLocal;
                $wallet->save();

                // Generate reference
                $reference = 'SB-' . strtoupper(uniqid());

                // Save to transactions table
                $transactionId = DB::table('transactions')->insertGetId([
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'request_id' => $orderResponse['order'],
                    'type' => 'social_boost',
                    // Use friendly provider name
                    'channel' => 'Easyget Social Package',
                    'provider' => 'Easyget Social Package',
                    'service_id' => $validated['service_id'],
                    'service_name' => $service['name'],
                    'service_logo' => $this->mtpHelper->getServiceLogo($service['category'], $service['name']),
                    'amount' => $costLocal,
                    'original_cost_usd' => $costUsd,
                    'status' => 'pending',
                    'description' => "Social Boost: {$service['name']} - " . number_format($validated['quantity']) . " units",
                    'response' => json_encode([
                        'link' => $validated['link'],
                        'quantity' => $validated['quantity'],
                        'service_type' => $service['type'],
                        'category' => $service['category'],
                        'comments' => $validated['comments'] ?? null,
                        'username' => $validated['username'] ?? null,
                        'usernames' => $validated['usernames'] ?? null,
                        'groups' => $validated['groups'] ?? null,
                        'media' => $validated['media'] ?? null,
                        'keywords' => $validated['keywords'] ?? null,
                        'hashtags' => $validated['hashtags'] ?? null,
                        'min' => $service['min'],
                        'max' => $service['max'],
                        'rate_per_k' => $service['rate'],
                        'refill' => $service['refill'] ?? false,
                        'cancel' => $service['cancel'] ?? false,
                        'order_placed_at' => now()->toIso8601String(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully! Your boost is being processed.',
                    'data' => [
                        'transaction_id' => $transactionId,
                        'transaction_reference' => $reference,
                        'mtp_order_id' => $orderResponse['order'],
                        'service_name' => $service['name'],
                        'quantity' => number_format($validated['quantity']),
                        'link' => $validated['link'],
                        'formatted_amount' => '₦' . number_format($costLocal, 2),
                        'formatted_balance' => '₦' . number_format($wallet->balance, 2),
                        'transaction_time' => now()->toIso8601String(),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Social boost purchase failed: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'service_id' => $validated['service_id'],
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process order: ' . $e->getMessage(),
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your input and try again.',
                'errors' => $e->errors(),
                'error_type' => 'validation'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Social boost purchase error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }

    /**
     * Check order status
     */
    public function checkStatus(Request $request)
    {
        try {
            $request->validate([
                'transaction_id' => 'required|integer'
            ]);

            $user = Auth::user();
            
            $transaction = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('id', $request->transaction_id)
                ->where('type', 'social_boost')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            if (in_array($transaction->status, ['completed', 'cancelled', 'refunded'])) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => ucfirst($transaction->status),
                        'local_status' => $transaction->status,
                        'message' => 'Order already ' . $transaction->status
                    ]
                ]);
            }

            $statusResponse = $this->mtpHelper->getOrderStatus($transaction->request_id);

            if (!$statusResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch order status from provider.',
                    'status' => 'pending'
                ]);
            }

            $localStatus = $this->mtpHelper->mapStatus($statusResponse['status'] ?? 'Pending');

            DB::table('transactions')
                ->where('id', $transaction->id)
                ->update([
                    'status' => $localStatus,
                    'response' => json_encode(array_merge(
                        json_decode($transaction->response, true) ?? [],
                        [
                            'mtp_status' => $statusResponse['status'] ?? null,
                            'start_count' => $statusResponse['start_count'] ?? null,
                            'remains' => $statusResponse['remains'] ?? null,
                            'charge' => $statusResponse['charge'] ?? null,
                            'last_checked' => now()->toIso8601String(),
                        ]
                    )),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $statusResponse['status'],
                    'local_status' => $localStatus,
                    'charge' => $statusResponse['charge'] ?? null,
                    'start_count' => $statusResponse['start_count'] ?? null,
                    'remains' => $statusResponse['remains'] ?? null,
                    'currency' => $statusResponse['currency'] ?? 'USD',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Status check error: ' . $e->getMessage(), [
                'transaction_id' => $request->transaction_id ?? null
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking status.',
                'status' => 'pending'
            ], 500);
        }
    }

    /**
     * Group services by category
     */
    private function groupServices($services)
    {
        $grouped = [];
        $skipped = 0;

        // Fetch all custom overrides from database (indexed by service_id)
        $custom = SocialServiceDescription::get()->keyBy('service_id');

        foreach ($services as $service) {
            // Skip services with missing required fields
            if (empty($service['service']) || empty($service['name'])) {
                $skipped++;
                Log::warning('Skipping service with missing required fields', [
                    'service' => $service
                ]);
                continue;
            }

            try {
                // Format service with local pricing
                $formatted = $this->mtpHelper->formatServiceForDisplay($service, $this->exchangeRate);

                // Apply any admin overrides (description, custom name, custom category)
                $svcId = $formatted['service_id'];
                if (isset($custom[$svcId])) {
                    $record = $custom[$svcId];
                    if (!empty($record->description)) {
                        $formatted['description'] = $record->description;
                    }
                    $formatted['custom_name'] = $record->custom_name ?? null;
                    $formatted['custom_category'] = $record->custom_category ?? null;
                    // Persist admin percentage so downstream price calcs can use it
                    $formatted['admin_percentage'] = intval($record->admin_percentage ?? 0);
                    if (!empty($record->custom_name)) {
                        $formatted['name'] = $record->custom_name;
                    }
                    if (!empty($record->custom_category)) {
                        $formatted['category'] = $record->custom_category;
                    }
                } else {
                    $formatted['custom_name'] = null;
                    $formatted['custom_category'] = null;
                    $formatted['admin_percentage'] = 0;
                }

                // Determine category to group by (after applying overrides)
                $category = !empty($formatted['category']) ? $formatted['category'] : 'Other';
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }

                // Attach client_price and provider percentage (if available)
                $serviceCode = $service['service'] ?? null;
                $pricing = $this->mtpHelper->getProductPricing($serviceCode);
                if (!empty($pricing) && isset($pricing['client_price']) && $pricing['client_price'] !== null) {
                    $clientPriceRaw = $pricing['client_price'];
                    $providerPercentage = intval($pricing['provider_percentage'] ?? 0);
                    $finalPerUnit = round($clientPriceRaw * (1 + ($providerPercentage / 100)), 2);

                    // base per-unit before admin percentage
                    $basePerUnit = $finalPerUnit;

                    // Apply admin percentage (if any)
                    $adminPct = intval($formatted['admin_percentage'] ?? 0);
                    if ($adminPct > 0) {
                        $mult = 1 + ($adminPct / 100);
                        $basePerUnit = round($basePerUnit * $mult, 2);
                    }

                    $formatted['client_price_raw'] = $clientPriceRaw; // raw database client_price
                    $formatted['client_price'] = $basePerUnit; // final per-unit price after provider+admin markup
                    $formatted['percentage_applied'] = $providerPercentage + ($formatted['admin_percentage'] ?? 0);
                    $formatted['pricing_source'] = 'product_database';

                    // Also normalize rate_local/per_unit to reflect final per-unit price
                    $formatted['rate_local_per_unit'] = round($basePerUnit, 4);
                    $formatted['rate_local'] = round($basePerUnit * 1000, 2);
                } else {
                    // Use API rate as fallback
                    $formatted['pricing_source'] = 'api_rate';
                    // Use admin percentage (if any) as the applied percentage for API-derived prices
                    $adminPct = intval($formatted['admin_percentage'] ?? 0);
                    $formatted['percentage_applied'] = $adminPct;

                    // Determine base per-unit from api-calculated value
                    $basePerUnit = $formatted['rate_local_per_unit'] ?? (isset($formatted['rate_local']) ? $formatted['rate_local'] / 1000 : 0);
                    if ($adminPct > 0) {
                        $mult = 1 + ($adminPct / 100);
                        $basePerUnit = round($basePerUnit * $mult, 4);
                    }

                    $formatted['rate_local_per_unit'] = $basePerUnit;
                    $formatted['rate_local'] = round($basePerUnit * 1000, 2);
                }

                $grouped[$category][] = $formatted;
            } catch (\Exception $e) {
                Log::warning('Failed to format service', [
                    'service_id' => $service['service'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $skipped++;
            }
        }

        Log::info('Services grouped', [
            'total_input' => count($services),
            'categories' => count($grouped),
            'skipped' => $skipped,
            'category_list' => array_keys($grouped)
        ]);

        // Sort categories alphabetically
        ksort($grouped);

        return $grouped;
    }

    /**
     * Admin: List all services for description management
     */
    public function adminListServices()
    {
        try {
            $services = $this->getCachedServices();

            // Fetch any custom names/categories/descriptions and key by service_id
            $custom = SocialServiceDescription::get()->keyBy('service_id');

            $formattedServices = [];
            foreach ($services as $service) {
                $formatted = $this->mtpHelper->formatServiceForDisplay($service, $this->exchangeRate);

                $svcId = $formatted['service_id'];
                $formatted['description'] = $custom[$svcId]->description ?? null;

                // Allow admin to override the service name, category, and apply admin percentage
                if (isset($custom[$svcId])) {
                    $record = $custom[$svcId];
                    $formatted['custom_name'] = $record->custom_name ?? null;
                    $formatted['custom_category'] = $record->custom_category ?? null;
                    $formatted['admin_percentage'] = intval($record->admin_percentage ?? 0);

                    if (!empty($record->custom_name)) {
                        $formatted['name'] = $record->custom_name;
                    }

                    if (!empty($record->custom_category)) {
                        $formatted['category'] = $record->custom_category;
                    }
                } else {
                    $formatted['custom_name'] = null;
                    $formatted['custom_category'] = null;
                    $formatted['admin_percentage'] = 0;
                }

                $formattedServices[] = $formatted;
            }

            return view('admin.social-services', ['services' => $formattedServices]);
        } catch (\Exception $e) {
            Log::error('Admin list services error', ['error' => $e->getMessage()]);
            return view('admin.social-services', ['services' => []])
                ->with('error', 'Failed to load services');
        }
    }

    /**
     * Admin: Refresh services cache
     */
    public function adminRefreshServices()
    {
        try {
            $cachePath = storage_path('app/cache/mtp_services.json');
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }

            $this->getCachedServices();

            return redirect()->route('admin.social.services')->with('success', 'Services refreshed successfully');
        } catch (\Exception $e) {
            Log::error('Admin refresh services error', ['error' => $e->getMessage()]);
            return redirect()->route('admin.social.services')->with('error', 'Failed to refresh services');
        }
    }

    /**
     * Admin: Update service description
     */
    public function adminUpdateDescription(Request $request)
    {
        try {
            $validated = $request->validate([
                'service_id' => 'required|string',
                'description' => 'nullable|string',
                'custom_name' => 'nullable|string|max:255',
                'custom_category' => 'nullable|string|max:255',
                'admin_percentage' => 'nullable|numeric|min:0',
            ]);
    
            SocialServiceDescription::updateOrCreate(
                ['service_id' => $validated['service_id']],
                [
                    'description' => $validated['description'] ?? null,
                    'custom_name' => $validated['custom_name'] ?? null,
                    'custom_category' => $validated['custom_category'] ?? null,
                    'admin_percentage' => intval($validated['admin_percentage'] ?? 0),
                ]
            );
    
            // Clear the cache so updates show immediately
            $cachePath = storage_path('app/cache/mtp_services.json');
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
    
            return redirect()->route('admin.social.services')->with('success', 'Description updated successfully');
        } catch (\Exception $e) {
            Log::error('Admin update description error', ['error' => $e->getMessage()]);
            return redirect()->route('admin.social.services')->with('error', 'Failed to update description');
        }
    }

    /**
     * Admin: Bulk-update admin_percentage across ALL services.
     *
     * Strategy (scalable to 5,000+ services):
     *   1. Validate the percentage input.
     *   2. Update every existing SocialServiceDescription row.
     *   3. Fetch all service IDs from cache that have NO saved record yet
     *      and insert them in chunks (avoids memory spikes on huge lists).
     *   4. Clear the services cache so the updated prices show immediately.
     */
    public function adminBulkUpdatePercentage(Request $request)
    {
        $validated = $request->validate([
            'global_percentage' => 'required|numeric|min:0|max:1000',
        ]);
    
        $percentage = intval($validated['global_percentage']);
    
        try {
            DB::beginTransaction();
    
            // ── Step 1: Update every row that already exists ──────────────────
            SocialServiceDescription::query()
                ->update(['admin_percentage' => $percentage]);
    
            // ── Step 2: Find service IDs with no saved record yet ─────────────
            $services = $this->getCachedServices();
    
            if (!empty($services)) {
                // Collect every service_id string from the API
                $allApiIds = collect($services)
                    ->pluck('service')
                    ->filter()
                    ->map(fn($id) => (string) $id)
                    ->unique()
                    ->values()
                    ->all();
    
                // Which ones already exist in the DB?
                $existingIds = SocialServiceDescription::whereIn('service_id', $allApiIds)
                    ->pluck('service_id')
                    ->all();
    
                $missingIds = array_diff($allApiIds, $existingIds);
    
                // Insert missing ones in chunks of 500 to stay memory-safe
                $now = now()->toDateTimeString();
                foreach (array_chunk($missingIds, 500) as $chunk) {
                    $rows = array_map(fn($id) => [
                        'service_id'       => $id,
                        'admin_percentage' => $percentage,
                        'description'      => null,
                        'custom_name'      => null,
                        'custom_category'  => null,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ], $chunk);
    
                    DB::table('social_service_descriptions')->insert($rows);
                }
            }
    
            DB::commit();
    
            // ── Step 3: Clear file cache so fresh prices render ───────────────
            $cachePath = storage_path('app/cache/mtp_services.json');
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
    
            $totalUpdated = SocialServiceDescription::count();
    
            return redirect()
                ->route('admin.social.services')
                ->with('success', "Global markup set to {$percentage}%. {$totalUpdated} services updated.");
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk percentage update failed', ['error' => $e->getMessage()]);
    
            return redirect()
                ->route('admin.social.services')
                ->with('error', 'Failed to apply global markup: ' . $e->getMessage());
        }
    }
}