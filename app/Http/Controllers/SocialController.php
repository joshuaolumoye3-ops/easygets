<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
   use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\Transaction;
// use App\Helpers\morethanpanel_helper;
use App\Helpers\morethanpanel_helper;



class SocialController extends Controller
{
public function services()
{

    $services = smm_api()->services();
    $categories = collect($services)->groupBy('category')->map(function ($services) {
        return $services->map(function ($service) {
            return (object) [
                'id'   => $service->service,   // access as object
                'name' => $service->name,
                'rate' => round($service->rate),
                'min'  => (int) $service->min,
                'max'  => (int) $service->max,
                'desc' => $service->desc ?? '',
            ];
        });

    });
 // dd($categories); // removed for production


    return view('users.socialPackage', compact('categories'));
}






public function order(Request $request)
{
    $request->validate([
        'service_id' => 'required|integer',
        'link'       => 'required|url',
        'quantity'   => 'required|integer|min:10',
    ]);

    DB::beginTransaction();

    try {
        // 1. Find service details from API
        $service = collect(smm_api()->services())
    ->firstWhere('id', $request->service_id);


        if (!$service) {
            throw new \Exception('Invalid service selected');
        }

        // 2. Calculate cost (rate is per 1000 units) + 5% markup
        $baseAmount = ($service->rate / 1000) * $request->quantity;
        $markupFee  = round($baseAmount * 0.05, 2);
        $amount     = round($baseAmount + $markupFee, 2);

        // 3. Lock wallet and check balance
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        // 4. Deduct immediately
        $wallet->decrement('balance', $amount);

        // 5. Place order on MoreThanPanel
        $response = smm_api()->order([
            'service'  => $request->service_id,
            'link'     => $request->link,
            'quantity' => $request->quantity,
        ]);

        // 6. Record transaction
        Transaction::create([
            'reference'   => uniqid('smm_'),
            'user_id'     => auth()->id(),
            'type'        => 'smm',
            'channel'     => 'MoreThanPanel',
            'provider'    => $service->name,
            'account'     => $request->link,
            'amount'      => $amount,
            'status'      => isset($response->order) ? 'success' : 'failed',
            'response'    => json_encode($response),
        ]);

        // 7. Commit or throw error
        if (isset($response->order)) {
            DB::commit();
            return back()->with('success', 'Order placed successfully. Order ID: ' . $response->order);
        } else {
            throw new \Exception($response->error ?? 'Order failed');
        }

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage())->withInput();
    }
}


}
