<?php
namespace App\Http\Controllers;

use App\Helpers\DemmyPayHelper;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Provider;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Models\EsimUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminServiceController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return view('admin.servicelist', compact('services'));
    }

    public function providers($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        $providers = $service->providers;
        return view('admin.serviceproviders', compact('service', 'providers'));
    }

    public function products($providerId)
    {
        $provider = Provider::findOrFail($providerId);


        // Fetch existing DB products for this provider
        $dbProducts = $provider->products->keyBy('product_code');
        $products = [];

        try {
            if ($provider->api_identifier === 'demmyglobal') {
                // Handle Data Products (Demmy Global)
                $response = DemmyPayHelper::getDataProducts();
                $apiProducts = $response['data'] ?? [];

                foreach ($apiProducts as $apiProduct) {
                    $productCode = (string)($apiProduct['id'] ?? '');
                    $apiPrice    = (float)($apiProduct['amount'] ?? 0);

                    $name = ($apiProduct['name'] ?? 'Unknown') . ' - ' .
                            ($apiProduct['size'] ?? 'N/A') . ' (' .
                            ($apiProduct['validity'] ?? 'N/A') . ')';

                    $dbProduct = $dbProducts->get($productCode);

                    $products[] = (object)[
                        'id'           => $dbProduct->id ?? null,
                        'product_code' => $productCode,
                        'category'     => strtoupper($apiProduct['network']['name'] ?? 'DATA'),
                        'name'         => $name,
                        'api_price'    => $apiPrice,
                        'client_price' => $dbProduct->client_price ?? null,
                        'is_active'    => $dbProduct->is_active ?? true,
                        'updated_at'   => $dbProduct->updated_at ?? now(),
                    ];
                }
            }elseif ($provider->api_identifier === 'clubkonnect-cable') {
                $userId = env('CLUBKONNECT_USERID');
                $url = "https://www.nellobytesystems.com/APICableTVPackagesV2.asp?UserID=" . $userId;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $responseBody = curl_exec($ch);
                $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error        = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    \Log::error('ClubKonnect Cable cURL Error:', ['error' => $error]);
                } elseif ($httpCode == 200) {
                    $apiResponse = json_decode($responseBody, true);

                    if (json_last_error() === JSON_ERROR_NONE && !empty($apiResponse['TV_ID'])) {
                        foreach ($apiResponse['TV_ID'] as $companyName => $companyArray) {
                            foreach ($companyArray as $companyData) {
                                if (!empty($companyData['PRODUCT']) && is_array($companyData['PRODUCT'])) {
                                    foreach ($companyData['PRODUCT'] as $product) {
                                        $productCode = (string)($product['PACKAGE_ID'] ?? '');
                                        $productName = $product['PACKAGE_NAME'] ?? 'Unknown';

                                        $apiPrice = (float)($product['PACKAGE_AMOUNT'] ?? 0);

                                        $dbProduct = $dbProducts->get($productCode);

                                        $products[] = (object)[
                                            'id'           => $dbProduct->id ?? null,
                                            'product_code' => $productCode,
                                            'category'     => strtoupper($companyName), // e.g. DSTV, GOTV
                                            'name'         => $productName,
                                            'api_price'    => $apiPrice,
                                            'client_price' => $dbProduct->client_price ?? null,
                                            'is_active'    => $dbProduct->is_active ?? true,
                                            'updated_at'   => $dbProduct->updated_at ?? now(),
                                        ];
                                    }
                                }
                            }
                        }
                    } else {
                        \Log::error('ClubKonnect Cable Invalid JSON:', [
                            'response_preview' => substr($responseBody, 0, 500)
                        ]);
                    }
                }
            }elseif ($provider->api_identifier === 'clubkonnect-electricity') {
                // Handle Electricity Products (ClubKonnect) - Using cURL directly
                $userId = env('CLUBKONNECT_USERID');
                $url = "https://www.nellobytesystems.com/APIElectricityDiscosV1.asp?UserID=" . $userId;

                // Use cURL directly as Laravel HTTP client might be blocked
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json, text/plain, */*',
                    'Accept-Language: en-US,en;q=0.9',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Cache-Control: no-cache',
                ]);

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    \Log::error('ClubKonnect cURL Error:', ['error' => $error]);
                } elseif ($httpCode == 200) {
                    // Log first 500 chars to see what we're getting
                    \Log::info('ClubKonnect Raw Response Preview:', [
                        'response_preview' => substr($responseBody, 0, 500),
                        'length' => strlen($responseBody)
                    ]);

                    // Try to parse as JSON
                    $apiResponse = json_decode($responseBody, true);

                    if (json_last_error() === JSON_ERROR_NONE && !empty($apiResponse['ELECTRIC_COMPANY'])) {
                        // Process the JSON response structure
                        foreach ($apiResponse['ELECTRIC_COMPANY'] as $companyKey => $companyDataArray) {
                            if (!empty($companyDataArray) && is_array($companyDataArray)) {
                                $companyData = $companyDataArray[0];

                                $companyId   = (string)($companyData['ID'] ?? '');
                                $companyName = $companyData['NAME'] ?? str_replace('_', ' ', $companyKey);

                                if (!empty($companyData['PRODUCT']) && is_array($companyData['PRODUCT'])) {
                                    foreach ($companyData['PRODUCT'] as $product) {
                                        $productCode = $companyId . '-' . ($product['PRODUCT_ID'] ?? '');
                                        $productName = $companyName . ' - ' . ucfirst($product['PRODUCT_TYPE'] ?? 'Unknown');

                                        $min = $product['MINAMOUNT'] ?? $product['MINIMUN_AMOUNT'] ?? 0;
                                        $max = $product['MAXAMOUNT'] ?? $product['MAXIMUM_AMOUNT'] ?? 0;

                                        $dbProduct = $dbProducts->get($productCode);

                                        $products[] = (object)[
                                            'id'           => $dbProduct->id ?? null,
                                            'product_code' => $productCode,
                                            'category'     => 'ELECTRICITY',
                                            'name'         => $productName . " (₦" . number_format($min) . " - ₦" . number_format($max) . ")",
                                            'api_price'    => 0,
                                            'client_price' => $dbProduct->client_price ?? null,
                                            'is_active'    => $dbProduct->is_active ?? true,
                                            'updated_at'   => $dbProduct->updated_at ?? now(),
                                        ];
                                    }
                                }
                            }
                        }
                    } else {
                        // If JSON parsing fails, maybe it's HTML error page - extract useful info
                        \Log::error('ClubKonnect API JSON parsing failed:', [
                            'json_error' => json_last_error_msg(),
                            'response_preview' => substr($responseBody, 0, 1000)
                        ]);

                        // Try to extract error message from HTML if present
                        if (strpos($responseBody, 'error') !== false || strpos($responseBody, 'Error') !== false) {
                            preg_match('/<title>(.*?)<\/title>/i', $responseBody, $matches);
                            $pageTitle = $matches[1] ?? 'Unknown error';
                            \Log::error('ClubKonnect API HTML Error:', ['page_title' => $pageTitle]);
                        }
                    }
                } else {
                    \Log::error('ClubKonnect API HTTP Error:', [
                        'http_code' => $httpCode,
                        'response' => substr($responseBody, 0, 500)
                    ]);
                }
            } else {
                // Fallback: show only DB products
                foreach ($provider->products as $dbProduct) {
                    $products[] = (object)[
                        'id'           => $dbProduct->id,
                        'product_code' => $dbProduct->product_code,
                        'category'     => $dbProduct->category ?? 'UNKNOWN',
                        'name'         => $dbProduct->name,
                        'api_price'    => $dbProduct->api_price ?? 0,
                        'client_price' => $dbProduct->client_price,
                        'is_active'    => $dbProduct->is_active ?? true,
                        'updated_at'   => $dbProduct->updated_at,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('Products fetch error:', [
                'provider' => $provider->api_identifier,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            // On error, fallback to showing DB products
            foreach ($provider->products as $dbProduct) {
                $products[] = (object)[
                    'id'           => $dbProduct->id,
                    'product_code' => $dbProduct->product_code,
                    'category'     => $dbProduct->category ?? 'UNKNOWN',
                    'name'         => $dbProduct->name,
                    'api_price'    => $dbProduct->api_price ?? 0,
                    'client_price' => $dbProduct->client_price,
                    'updated_at'   => $dbProduct->updated_at,
                ];
            }
        }

        return view('admin.servicesprice', compact('provider', 'products'));
    }


    public function saveProducts(Request $request, $providerId)
    {
        $provider = Provider::findOrFail($providerId);

        try {
            if ($provider->api_identifier === 'demmyglobal') {
                // Handle Data Products (Demmy Global)
                $response = DemmyPayHelper::getDataProducts();
                $apiProducts = $response['data'] ?? [];
                $clientPrice = $request->input('client_price');

                foreach ($apiProducts as $apiProduct) {
                    $productCode = (string)($apiProduct['id']);
                    $apiPrice    = (float)($apiProduct['amount']);

                    $productName = ($apiProduct['name'] ?? 'Unknown') .
                                ' - ' . ($apiProduct['size'] ?? 'N/A') .
                                ' (' . ($apiProduct['validity'] ?? 'N/A') . ')';

                    Product::updateOrCreate(
                        [
                            'provider_id'  => $provider->id,
                            'product_code' => $productCode,
                        ],
                        [
                            'name'         => $productName,
                            'category'     => strtoupper($apiProduct['network']['name'] ?? 'DATA'),
                            'api_price'    => $apiPrice,
                            'client_price' => $clientPrice,
                        ]
                    );
                }
            }elseif($provider->api_identifier === 'morethanpanel'){
                $clientPrice = $request->input('client_price');
                Product::updateOrCreate(
                        [
                            'provider_id'  => $provider->id,
                            'product_code' => 'morethanpanel',
                        ],
                        [
                            'name'         => 'MoreThanPanel',
                            'category'     => 'social',
                            'api_price'    => 0,
                            'client_price' => $clientPrice,
                        ]
                    );


            } elseif ($provider->api_identifier === 'clubkonnect-electricity') {
                // Handle Electricity Products
                $userId = env('CLUBKONNECT_USERID');
                $response = Http::get("https://www.nellobytesystems.com/APIElectricityDiscosV1.asp", [
                    'UserID' => $userId
                ]);

                if ($response->ok()) {
                    $apiResponse = $response->json();
                    $clientPrice = $request->input('client_price');

                    foreach ($apiResponse as $discoKey => $discoArray) {
                        foreach ($discoArray as $disco) {
                            $companyId   = (string)($disco['ID'] ?? '');
                            $companyName = $disco['NAME'] ?? 'Unknown Disco';

                            foreach ($disco['PRODUCT'] as $product) {
                                $productCode = $companyId . '-' . ($product['PRODUCT_ID'] ?? '');
                                $productName = $companyName . ' - ' . ucfirst($product['PRODUCT_TYPE'] ?? 'Unknown');

                                $min = $product['MINIMUN_AMOUNT'] ?? $product['MINAMOUNT'] ?? 0;
                                $max = $product['MAXIMUM_AMOUNT'] ?? $product['MAXAMOUNT'] ?? 0;

                                Product::updateOrCreate(
                                    [
                                        'provider_id'  => $provider->id,
                                        'product_code' => $productCode,
                                    ],
                                    [
                                        'name'         => $productName . " (₦{$min} - ₦{$max})",
                                        'category'     => 'ELECTRICITY',
                                        'api_price'    => 0,
                                        'client_price' => $clientPrice,
                                    ]
                                );
                            }
                        }
                    }
                }
            } elseif ($provider->api_identifier === 'clubkonnect-cable') {
                // Handle Cable TV Products
                $userId = env('CLUBKONNECT_USERID');
                $response = Http::get("https://www.nellobytesystems.com/APICableTVPackagesV2.asp", [
                    'UserID' => $userId
                ]);

                if ($response->ok()) {
                    $apiResponse = $response->json();
                    $clientPrice = $request->input('client_price');

                    if (!empty($apiResponse['TV_ID'])) {
                        foreach ($apiResponse['TV_ID'] as $companyName => $companyArray) {
                            foreach ($companyArray as $companyData) {
                                if (!empty($companyData['PRODUCT']) && is_array($companyData['PRODUCT'])) {
                                    foreach ($companyData['PRODUCT'] as $product) {
                                        $productCode = (string)($product['PACKAGE_ID'] ?? '');
                                        $productName = $product['PACKAGE_NAME'] ?? 'Unknown';
                                        $apiPrice    = (float)($product['PACKAGE_AMOUNT'] ?? 0);

                                        Product::updateOrCreate(
                                            [
                                                'provider_id'  => $provider->id,
                                                'product_code' => $productCode,
                                            ],
                                            [
                                                'name'         => $productName,
                                                'category'     => strtoupper($companyName), // e.g. DSTV, GOTV, STARTIMES
                                                'api_price'    => $apiPrice,
                                                'client_price' => $clientPrice,
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif ($provider->api_identifier === 'Admin-otp' || $provider->api_identifier === 'otp') {
            // ✅ Handle OTP Provider Products
            $clientPrice = $request->input('client_price');

            Product::updateOrCreate(
                [
                    'provider_id'  => $provider->id,
                    'product_code' => 'otp-verification',
                ],
                [
                    'name'         => 'OTP Verification Service',
                    'category'     => 'OTP',
                    'api_price'    => 0,
                    'client_price' => $clientPrice,
                ]
            );
        }

        return redirect()->back()->with('success', 'All plans saved successfully.');
    } catch (\Exception $e) {
        \Log::error('SaveProducts Error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ]);

        return redirect()->back()->with('error', 'Failed to fetch and save products.');
    }
}


    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        // Soft-deactivate instead of hard delete so it can be reactivated later
        $product->is_active = false;
        $product->save();

        return redirect()->back()->with('success', 'Product deactivated successfully. You can reactivate it later.');
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update([
            'client_price' => $request->client_price
        ]);
        return back()->with('success', 'Product price updated.');
    }

    /**
     * Create or reactivate a single product (used when an API product is saved individually)
     */
    public function createProduct(Request $request, $providerId)
    {
        $request->validate([
            'product_code' => 'required|string',
            'name' => 'required|string',
            'api_price' => 'nullable|numeric',
            'client_price' => 'required|numeric',
        ]);

        $provider = Provider::findOrFail($providerId);

        $product = Product::updateOrCreate(
            [
                'provider_id' => $provider->id,
                'product_code' => $request->product_code,
            ],
            [
                'name' => $request->name,
                'category' => $request->category ?? 'UNKNOWN',
                'api_price' => $request->api_price ?? 0,
                'client_price' => $request->client_price,
                'is_active' => true,
            ]
        );

        return redirect()->back()->with('success', 'Product saved/reactivated successfully.');
    }

    /**
     * Toggle product active status (reactivate/deactivate)
     */
    public function toggleActive($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        $message = $product->is_active ? 'Product reactivated successfully.' : 'Product deactivated successfully.';
        return redirect()->back()->with('success', $message);
    }

    public function applyMarkup(Request $request, $providerId)
    {
        $provider = Provider::findOrFail($providerId);
        $markup = $request->markup;
        $type = $request->type;

        foreach ($provider->products as $product) {
            $newPrice = $type === 'percent'
                ? $product->api_price + ($product->api_price * $markup / 100)
                : $product->api_price + $markup;

            $product->update(['client_price' => $newPrice]);
        }

        return back()->with('success', 'Markup applied to all plans.');
    }

     /**
     * Display all eSIM transactions
     */
    public function esimindex(Request $request)
    {
        // Get only eSIM transactions using the 'type' column
        $transactions = Transaction::where('type', 'esim')
            ->latest()
            ->paginate(15);
        // dd($transactions); // removed for production
        return view('admin.esim', compact('transactions'));
    }

    /**
     * Show transaction details as JSON (for modal).
     */
    public function show($id)
    {
        $txn = Transaction::with('user')->findOrFail($id);

        return response()->json([
            'id'         => $txn->id,
            'user'       => [
                'name'  => $txn->user->name ?? 'N/A',
                'email' => $txn->user->email ?? 'N/A',
            ],
            'plan_name'  => $txn->plan_name ?? 'N/A',
            'amount'     => $txn->amount,
            'status'     => $txn->status,
            'created_at' => $txn->created_at->format('d M Y h:i A'),
        ]);
    }

    /**
     * Handle admin upload (numbers, details, images).
     */
    /**
 * Upload or Update eSIM details for a transaction
 */
public function upload(Request $request)
{
    $request->validate([
        'transaction_id' => 'required|exists:transactions,id',
        'esim_upload_id' => 'nullable|exists:esim_uploads,id',
        'numbers' => 'nullable|string',
        'details' => 'nullable|string',
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max per image
    ]);

    try {
        DB::beginTransaction();

        $transaction = \App\Models\Transaction::findOrFail($request->transaction_id);
        
        // Check if we're updating or creating
        $esimUpload = null;
        if ($request->esim_upload_id) {
            $esimUpload = \App\Models\EsimUpload::findOrFail($request->esim_upload_id);
        } else {
            $esimUpload = \App\Models\EsimUpload::where('transaction_id', $transaction->id)->first();
        }

        // Prepare data for update/create
        $data = [
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
        ];

        // Only update fields that were provided
        if ($request->filled('numbers')) {
            $data['number'] = $request->numbers;
        }

        if ($request->filled('details')) {
            $data['details'] = $request->details;
        }

        // Handle image uploads
        $existingImages = [];
        if ($esimUpload && $esimUpload->images) {
            // Get existing images
            $existingImages = is_array($esimUpload->images) 
                ? $esimUpload->images 
                : json_decode($esimUpload->images, true) ?? [];
        }

        if ($request->hasFile('images')) {
            $newImages = [];
            
            foreach ($request->file('images') as $image) {
                try {
                    // Create directory if it doesn't exist
                    $directory = 'uploads/esim';
                    if (!file_exists(public_path($directory))) {
                        mkdir(public_path($directory), 0755, true);
                    }

                    // Generate unique filename
                    $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $path = $directory . '/' . $filename;

                    // Move file to public directory
                    $image->move(public_path($directory), $filename);

                    $newImages[] = $path;
                    
                    Log::info('Image uploaded successfully', [
                        'filename' => $filename,
                        'path' => $path
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error uploading image', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Merge existing and new images and store as an array (Eloquent will JSON-cast it)
            $allImages = array_merge($existingImages, $newImages);
            $data['images'] = $allImages;
        }

        // Update or create the eSIM upload record
        if ($esimUpload) {
            // Update existing record (only update provided fields)
            $esimUpload->update($data);
            $action = 'updated';
        } else {
            // Create new record
            $esimUpload = EsimUpload::create($data);
            $action = 'created';
        }

        // Mark the parent transaction as successful now that eSIM details (and files) are attached
        try {
            $transaction->status = 'Success';
            $transaction->save();
        } catch (\Exception $e) {
            Log::error('Failed to update transaction status after eSIM upload', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }

        DB::commit();

        Log::info("eSIM details {$action}", [
            'esim_upload_id' => $esimUpload->id,
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'has_images' => $request->hasFile('images'),
            'image_count' => $request->hasFile('images') ? count($request->file('images')) : 0
        ]);

        return redirect()->back()->with('success', "eSIM details {$action} successfully!");

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Error uploading eSIM details', [
            'transaction_id' => $request->transaction_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()->with('error', 'Failed to save eSIM details: ' . $e->getMessage());
    }
}

}
