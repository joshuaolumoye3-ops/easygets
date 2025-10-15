<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EsimCountry;
use App\Models\EsimPackage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AdminEsimController extends Controller
{
    // Fetch countries from API or DB
    public function index()
    {
        // Fetch from API only if DB is empty
        if (EsimCountry::count() == 0) {
            $response = Http::get('https://restcountries.com/v3.1/all');

            if ($response->ok()) {
                $countries = collect($response->json())->map(function ($country) {
                    return [
                        'country_name' => $country['name']['common'] ?? 'Unknown',
                        'country_code' => strtoupper($country['cca2'] ?? ''),
                        'flag_url'     => $country['flags']['png'] ?? null,
                        'base_price'   => 0,
                    ];
                })->filter(fn($c) => !empty($c['country_code']));

                EsimCountry::insert($countries->toArray());
            }
        }

        $countries = EsimCountry::orderBy('country_name')->get();
        return view('admin.esim.index', compact('countries'));
    }

    // Update or create base price for country
    public function updatePrice(Request $request, $id)
    {
        $request->validate(['base_price' => 'required|numeric|min:0']);

        $country = EsimCountry::findOrFail($id);
        $country->update(['base_price' => $request->base_price]);

        return response()->json(['success' => true, 'message' => 'Country price updated successfully']);
    }

    // Fetch and manage packages for a country
    public function packages($countryId)
    {
        $country = EsimCountry::findOrFail($countryId);
        $packages = $country->packages()->orderBy('price')->get();

        return response()->json(['country' => $country, 'packages' => $packages]);
    }

    // Create or update package
    public function savePackage(Request $request, $countryId)
    {
        $request->validate([
            'plan' => 'required|string|max:50',
            'validity_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'package_id' => 'nullable|exists:esim_packages,id'
        ]);

        $country = EsimCountry::findOrFail($countryId);

        $package = EsimPackage::updateOrCreate(
            ['id' => $request->package_id],
            [
                'esim_country_id' => $country->id,
                'plan' => $request->plan,
                'validity_days' => $request->validity_days,
                'price' => $request->price,
            ]
        );

        return response()->json(['success' => true, 'package' => $package]);
    }

    // Delete package
    public function deletePackage($id)
    {
        $package = EsimPackage::findOrFail($id);
        $package->delete();

        return response()->json(['success' => true, 'message' => 'Package deleted successfully']);
    }
}
