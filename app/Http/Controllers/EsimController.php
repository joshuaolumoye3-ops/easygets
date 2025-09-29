<?php
namespace App\Http\Controllers;

use App\Helpers\PinHelper;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EsimController extends Controller
{
    public function index()
    {
        // Cache for 1 day (you can increase to weeks/months if you want)
        $countries = Cache::remember('countries_list', now()->addDays(30), function () {
            $list = [];

            try {
                $response = Http::timeout(15)->get('https://restcountries.com/v3.1/all', [
                    'fields' => 'cca2,name'
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    foreach ($data as $country) {
                        $list[] = [
                            'id'    => $country['cca2'],
                            'code'  => $country['cca2'],
                            'title' => $country['name']['common'],
                            'price' => rand(4, 12),
                        ];
                    }

                    // Sort alphabetically by title
                    usort($list, fn($a, $b) => strcmp($a['title'], $b['title']));
                } else {
                    \Log::error('RestCountries API failed', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching countries', ['message' => $e->getMessage()]);
            }

            return $list;
        });

        return view('users.esim', compact('countries'));
    }

    public function plan($code)
{
    // Get cached countries
    $countries = Cache::remember('countries_list', now()->addDays(30), function () {
        $list = [];

        try {
            $response = Http::timeout(15)->get('https://restcountries.com/v3.1/all', [
                'fields' => 'cca2,name'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                foreach ($data as $country) {
                    $list[] = [
                        'id'    => $country['cca2'],
                        'code'  => strtoupper($country['cca2']),
                        'title' => $country['name']['common'],
                        'price' => rand(4, 12),
                    ];
                }

                usort($list, fn($a, $b) => strcmp($a['title'], $b['title']));
            } else {
                \Log::error('RestCountries API failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching countries', ['message' => $e->getMessage()]);
        }

        return $list;
    });

    // Find selected country by code (case insensitive)
    $country = collect($countries)->firstWhere('code', strtoupper($code));

    if (!$country) {
        abort(404, 'Country not found');
    }

    // Mock packages for now
    $packages = [
        ['id' => 1, 'title' => '1GB', 'validity' => '7 days', 'price' => 4.50],
        ['id' => 2, 'title' => '2GB', 'validity' => '15 days', 'price' => 6.50],
        ['id' => 3, 'title' => '3GB', 'validity' => '30 days', 'price' => 8.50],
        ['id' => 4, 'title' => '5GB', 'validity' => '30 days', 'price' => 11.50],
        ['id' => 5, 'title' => '10GB', 'validity' => '30 days', 'price' => 18.00],
        ['id' => 6, 'title' => '20GB', 'validity' => '30 days', 'price' => 34.00],
        ['id' => 7, 'title' => 'Unlimited', 'validity' => '10 days', 'price' => 35.00],
    ];

    $features = [
            'network' => '4G LTE / 5G',
            'speed'   => 'High speed (depends on local provider)',
            'phone'   => 'No local phone number',
            'topup'   => 'Not supported',
    ];

    return view('users.esim-plan', compact('country', 'packages', 'features'));
}


    public function checkout($code, $packageId)
    {
        // Get country from cache
        $countries = Cache::get('countries_list', []);
        $country = collect($countries)->firstWhere('code', strtoupper($code));
        if (!$country) abort(404, 'Country not found');

        // Mock packages (same as before)
        $packages = [
            ['id' => 1, 'title' => '1GB', 'validity' => '7 days', 'price' => 4.50],
            ['id' => 2, 'title' => '2GB', 'validity' => '15 days', 'price' => 6.50],
            ['id' => 3, 'title' => '3GB', 'validity' => '30 days', 'price' => 8.50],
            ['id' => 4, 'title' => '5GB', 'validity' => '30 days', 'price' => 11.50],
            ['id' => 5, 'title' => '10GB', 'validity' => '30 days', 'price' => 18.00],
            ['id' => 6, 'title' => '20GB', 'validity' => '30 days', 'price' => 34.00],
            ['id' => 7, 'title' => 'Unlimited', 'validity' => '10 days', 'price' => 35.00],
        ];

        $package = collect($packages)->firstWhere('id', $packageId);
        if (!$package) abort(404, 'Package not found');

        return view('users.esim-checkout', compact('country', 'package'));
    }

    public function store(Request $request, string $countryCode, string $packageId)
    {
        $request->validate([
            'pin' => 'required|array|size:4',
            'pin.*' => 'required|numeric|digits:1',
        ]);

        // Combine PIN array into one string
        $inputPin = implode('', $request->pin);

        // Verify PIN
        PinHelper::verifyPin($inputPin);

        // Example: lookup package price from DB/API (replace with your logic)
        $packagePrice = 10; // <-- replace with actual package price
        $providerName = strtoupper($countryCode); // you can change this if needed

        // Create transaction
        $transaction = Transaction::create([
            'reference'  => Str::uuid()->toString(),
            'user_id'    => auth()->id(),
            'type'       => 'eSIM Purchase',
            'channel'    => 'web',
            'provider'   => $providerName,
            'phone'      => null, // optional if not tied to phone
            'recipient'  => $countryCode, // store country code as recipient
            'amount'     => $packagePrice,
            'status'     => 'Pending',
            'response'   => null, // will be filled after API response
        ]);

        return redirect()
            ->route('transactions.show', $transaction->id)
            ->with('success', 'Transaction created successfully and is pending.');
    }
}
