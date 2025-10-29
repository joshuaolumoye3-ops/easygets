<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\PinHelper;
use App\Helpers\MoreThanPanelHelper;

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
            $isDev = config('app.debug');
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

            // Validate request
            $validated = $request->validate([
                'service_id' => 'required|integer',
                'link' => 'required|url|max:500',
                'quantity' => 'required|integer|min:1',
                'pin' => 'required|digits:4',
                'comments' => 'nullable|string|max:5000',
                'username' => 'nullable|string|max:100',
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

            // Calculate cost
            $costs = $this->mtpHelper->calculateCost($service['rate'], $validated['quantity'], $this->exchangeRate);
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

                // Add additional fields based on service type
                if ($service['type'] === 'Custom Comments' && !empty($validated['comments'])) {
                    $orderData['comments'] = $validated['comments'];
                }
                
                if (strpos($service['type'], 'Mentions') !== false && !empty($validated['username'])) {
                    $orderData['username'] = $validated['username'];
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
                    'channel' => 'morethanpanel',
                    'provider' => 'morethanpanel',
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
        
        foreach ($services as $service) {
            // Skip services with missing required fields
            if (!isset($service['category']) || !isset($service['service'])) {
                $skipped++;
                continue;
            }
            
            $category = $service['category'];
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            try {
                // Format service with local pricing
                $formatted = $this->mtpHelper->formatServiceForDisplay($service, $this->exchangeRate);
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
            'skipped' => $skipped
        ]);

        // Sort categories alphabetically
        ksort($grouped);

        return $grouped;
    }
}