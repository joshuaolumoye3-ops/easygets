<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EsimCountry;
use App\Models\EsimPackage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AdminEsimController extends Controller
{
    /**
     * Display list of eSIM countries
     */
    public function index()
    {
        // Fetch from API only if DB is empty
        if (EsimCountry::count() == 0) {
            $this->fetchCountriesFromAPI();
        }

        $countries = EsimCountry::orderBy('country_name')->get();
        return view('admin.esim.index', compact('countries'));
    }

    /**
     * Fetch countries from RestCountries API
     */
    private function fetchCountriesFromAPI()
    {
        try {
            $response = Http::timeout(30)->get('https://restcountries.com/v3.1/all', [
                'fields' => 'cca2,name,flags'
            ]);

            if ($response->failed()) {
                Log::error('Failed to fetch countries from RestCountries API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return;
            }

            $responseData = $response->json();

            if (empty($responseData)) {
                Log::error('No data returned from RestCountries API');
                return;
            }

            $countries = collect($responseData)
                ->map(function ($country) {
                    $countryCode = strtoupper($country['cca2'] ?? '');
                    $countryName = $country['name']['common'] ?? 'Unknown';
                    
                    // Get flag URL (RestCountries provides both PNG and SVG)
                    $flagUrl = $country['flags']['png'] ?? 
                               "https://flagcdn.com/w320/" . strtolower($countryCode) . ".png";
                    
                    return [
                        'country_name' => $countryName,
                        'country_code' => $countryCode,
                        'flag_url'     => $flagUrl,
                        'base_price'   => 0,
                        'is_active'    => true,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                })
                ->filter(function($country) {
                    return !empty($country['country_code']) && 
                           $country['country_name'] !== 'Unknown' &&
                           strlen($country['country_code']) == 2;
                })
                ->unique('country_code')
                ->sortBy('country_name')
                ->values()
                ->toArray();

            if (empty($countries)) {
                Log::warning('No valid countries found after filtering API response');
                return;
            }

            // Insert in chunks to avoid memory issues
            $inserted = 0;
            foreach (array_chunk($countries, 50) as $chunk) {
                foreach ($chunk as $country) {
                    EsimCountry::updateOrCreate(
                        ['country_code' => $country['country_code']],
                        $country
                    );
                    $inserted++;
                }
            }

            Log::info('Successfully fetched and stored eSIM countries', [
                'total_countries' => count($countries),
                'inserted' => $inserted
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching eSIM countries from API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Manually refresh countries from API
     */
    public function refreshCountries()
    {
        try {
            $countBefore = EsimCountry::count();
            
            // Clear any existing cache
            Cache::forget('countries_list');
            
            $this->fetchCountriesFromAPI();
            
            $countAfter = EsimCountry::count();
            $newCountries = $countAfter - $countBefore;
            
            return response()->json([
                'success' => true,
                'message' => "Countries refreshed successfully. Added {$newCountries} new countries.",
                'total_count' => $countAfter
            ]);
        } catch (\Exception $e) {
            Log::error('Error refreshing countries', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh countries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update base price for a country
     */
    public function updatePrice(Request $request, $id)
    {
        $request->validate([
            'base_price' => 'required|numeric|min:0'
        ]);

        try {
            $country = EsimCountry::findOrFail($id);
            $oldPrice = $country->base_price;
            $country->update(['base_price' => $request->base_price]);

            Log::info("Updated base price for {$country->country_name}", [
                'country_code' => $country->country_code,
                'old_price' => $oldPrice,
                'new_price' => $request->base_price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Country price updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating price', [
                'country_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update price'
            ], 500);
        }
    }

    /**
     * Get packages for a specific country
     */
    public function packages($countryId)
    {
        try {
            $country = EsimCountry::findOrFail($countryId);
            $packages = $country->packages()
                ->where('is_active', true)
                ->orderBy('validity_days')
                ->orderBy('price')
                ->get();

            return response()->json([
                'success' => true,
                'country' => $country,
                'packages' => $packages
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching packages', [
                'country_id' => $countryId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch packages',
                'packages' => []
            ], 404);
        }
    }

    /**
     * Create or update a package
     */
    public function savePackage(Request $request, $countryId)
    {
        $request->validate([
            'plan' => 'required|string|max:50',
            'validity_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'package_id' => 'nullable|exists:esim_packages,id'
        ]);

        try {
            DB::beginTransaction();

            $country = EsimCountry::findOrFail($countryId);

            $package = EsimPackage::updateOrCreate(
                ['id' => $request->package_id],
                [
                    'esim_country_id' => $country->id,
                    'plan' => $request->plan,
                    'validity_days' => $request->validity_days,
                    'price' => $request->price,
                    'is_active' => true,
                ]
            );

            DB::commit();

            Log::info("Package saved for {$country->country_name}", [
                'package_id' => $package->id,
                'plan' => $package->plan,
                'validity_days' => $package->validity_days,
                'price' => $package->price,
                'action' => $request->package_id ? 'updated' : 'created'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package saved successfully',
                'package' => $package
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving package', [
                'country_id' => $countryId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save package'
            ], 500);
        }
    }

    /**
     * Delete a package
     */
    public function deletePackage($id)
    {
        try {
            $package = EsimPackage::findOrFail($id);
            $packageInfo = [
                'id' => $package->id,
                'plan' => $package->plan,
                'country' => $package->country->country_name
            ];
            
            $package->delete();

            Log::info("Deleted package", $packageInfo);

            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting package', [
                'package_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete package'
            ], 500);
        }
    }

    /**
     * Update a package via AJAX
     */
    public function updatePackage(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'plan' => 'required|string|max:100',
                'validity_days' => 'required|integer|min:1|max:365',
                'price' => 'required|numeric|min:0'
            ]);

            $package = EsimPackage::findOrFail($id);
            $package->update([
                'plan' => $validated['plan'],
                'validity_days' => $validated['validity_days'],
                'price' => $validated['price'],
            ]);

            Log::info('Package updated via AJAX', [
                'package_id' => $package->id,
                'plan' => $package->plan,
                'validity_days' => $package->validity_days,
                'price' => $package->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package updated successfully',
                'package' => $package
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', array_merge(...array_values($e->errors())))
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating package via AJAX', [
                'package_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update package: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle country active status
     */
    public function toggleCountryStatus($id)
    {
        try {
            $country = EsimCountry::findOrFail($id);
            $country->is_active = !$country->is_active;
            $country->save();

            Log::info("Toggled country status", [
                'country' => $country->country_name,
                'country_code' => $country->country_code,
                'new_status' => $country->is_active ? 'active' : 'inactive'
            ]);

            return response()->json([
                'success' => true,
                'message' => $country->is_active ? 'Country activated' : 'Country deactivated',
                'status' => $country->is_active
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling country status', [
                'country_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update country status'
            ], 500);
        }
    }

    /**
     * Toggle local phone number availability flag
     */
    public function toggleLocalNumber($id)
    {
        try {
            $country = EsimCountry::findOrFail($id);
            $country->local_number_available = !$country->local_number_available;
            $country->save();

            Log::info("Toggled local number availability", [
                'country' => $country->country_name,
                'country_code' => $country->country_code,
                'local_number_available' => $country->local_number_available
            ]);

            return response()->json([
                'success' => true,
                'message' => $country->local_number_available ? 'Local numbers enabled' : 'Local numbers disabled',
                'local_number_available' => $country->local_number_available
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling local number availability', [
                'country_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update local number availability'
            ], 500);
        }
    }

    /**
     * Seed database with sample countries manually (for testing)
     */
    public function seedSampleCountries()
    {
        try {
            $sampleCountries = [
                ['country_name' => 'United States', 'country_code' => 'US', 'flag_url' => 'https://flagcdn.com/w320/us.png', 'base_price' => 5000, 'is_active' => true],
                ['country_name' => 'United Kingdom', 'country_code' => 'GB', 'flag_url' => 'https://flagcdn.com/w320/gb.png', 'base_price' => 4500, 'is_active' => true],
                ['country_name' => 'Canada', 'country_code' => 'CA', 'flag_url' => 'https://flagcdn.com/w320/ca.png', 'base_price' => 4800, 'is_active' => true],
                ['country_name' => 'Nigeria', 'country_code' => 'NG', 'flag_url' => 'https://flagcdn.com/w320/ng.png', 'base_price' => 3000, 'is_active' => true],
                ['country_name' => 'Germany', 'country_code' => 'DE', 'flag_url' => 'https://flagcdn.com/w320/de.png', 'base_price' => 4200, 'is_active' => true],
                ['country_name' => 'France', 'country_code' => 'FR', 'flag_url' => 'https://flagcdn.com/w320/fr.png', 'base_price' => 4300, 'is_active' => true],
                ['country_name' => 'Japan', 'country_code' => 'JP', 'flag_url' => 'https://flagcdn.com/w320/jp.png', 'base_price' => 5500, 'is_active' => true],
                ['country_name' => 'Australia', 'country_code' => 'AU', 'flag_url' => 'https://flagcdn.com/w320/au.png', 'base_price' => 5200, 'is_active' => true],
                ['country_name' => 'United Arab Emirates', 'country_code' => 'AE', 'flag_url' => 'https://flagcdn.com/w320/ae.png', 'base_price' => 4000, 'is_active' => true],
                ['country_name' => 'South Africa', 'country_code' => 'ZA', 'flag_url' => 'https://flagcdn.com/w320/za.png', 'base_price' => 3500, 'is_active' => true],
            ];

            $inserted = 0;
            foreach ($sampleCountries as $country) {
                EsimCountry::updateOrCreate(
                    ['country_code' => $country['country_code']],
                    $country
                );
                $inserted++;
            }

            Log::info('Sample countries seeded successfully', [
                'count' => $inserted
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully seeded {$inserted} sample countries",
                'count' => $inserted
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error seeding countries', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to seed countries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
 * ✅ Fetch eSIM uploads and related transaction details for admin popup
 */

public function showTransaction($id)
{
    try {
        $transaction = \App\Models\Transaction::with(['user', 'esimUpload'])
            ->findOrFail($id);

        $esimData = null;
        $hasExistingUpload = false;

        if ($transaction->esimUpload) {
            $esim = $transaction->esimUpload;
            $hasExistingUpload = true;

            // Handle images - they could be JSON string or already decoded array
            $images = $esim->images;
            if (is_string($images)) {
                $images = json_decode($images, true) ?? [];
            }
            
            $esimData = [
                'id'      => $esim->id,
                'number'  => $esim->number ?? '',
                'details' => $esim->details ?? '',
                'images'  => collect($images)
                    ->map(fn($img) => asset($img))
                    ->toArray(),
                'created' => optional($esim->created_at)->format('d M Y, h:i A'),
                'updated' => optional($esim->updated_at)->format('d M Y, h:i A'),
            ];
        }

        return response()->json([
            'success' => true,
            'transaction' => [
                'id'         => $transaction->id,
                'username'   => $transaction->user->username ?? 'N/A',
                'user_email' => $transaction->user->email ?? 'N/A',
                'amount'     => $transaction->amount,
                'status'     => $transaction->status,
                'reference'  => $transaction->reference,
                'type'       => $transaction->type,
                'channel'    => $transaction->channel ?? 'N/A',
                'provider'   => $transaction->provider ?? 'N/A',
                'phone'      => $transaction->phone ?? 'N/A',
                'recipient'  => $transaction->recipient ?? 'N/A',
                'created_at' => $transaction->created_at->format('d M Y, h:i A'),
            ],
            'esim' => $esimData,
            'hasExistingUpload' => $hasExistingUpload,
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching eSIM transaction details', [
            'transaction_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch eSIM details: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * Get packages for AJAX requests (returns array only)
     */
    public function getPackages($id)
    {
        try {
            $country = EsimCountry::findOrFail($id);
            $packages = $country->packages()
                ->where('is_active', true)
                ->orderBy('validity_days')
                ->orderBy('price')
                ->get()
                ->toArray();

            return response()->json($packages);
        } catch (\Exception $e) {
            Log::error('Error fetching packages for AJAX', [
                'country_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([], 404);
        }
    }

    /**
     * Store package from AJAX JSON request
     */
    public function storePackage(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'plan' => 'required|string|max:100',
                'validity_days' => 'required|integer|min:1|max:365',
                'price' => 'required|numeric|min:0'
            ]);

            $country = EsimCountry::findOrFail($id);

            $package = EsimPackage::create([
                'esim_country_id' => $country->id,
                'plan' => $validated['plan'],
                'validity_days' => $validated['validity_days'],
                'price' => $validated['price'],
                'is_active' => true,
            ]);

            Log::info("Package created via AJAX", [
                'country_id' => $id,
                'package_id' => $package->id,
                'plan' => $package->plan
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package saved successfully',
                'package' => $package
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error creating package', [
                'country_id' => $id,
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_merge(...array_values($e->errors())))
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing package via AJAX', [
                'country_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save package: ' . $e->getMessage()
            ], 500);
        }
    }
}