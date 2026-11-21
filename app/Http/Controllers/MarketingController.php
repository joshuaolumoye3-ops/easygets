<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MarketingController extends Controller
{
    public function home() { return view('marketing.home'); }
    public function services() { return view('marketing.services'); }
    public function about() { return view('marketing.about'); }
    public function contact() { return view('marketing.contact'); }
    public function terms() { return view('marketing.terms'); }
    public function privacy() { return view('marketing.privacy'); }
    public function refund() { return view('marketing.refund'); }

    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name'  => 'required|string|max:60',
            'email'      => 'required|email|max:150',
            'phone'      => 'required|string|max:20',
            'message'    => 'required|string|max:2000',
        ]);

        // Swap this for your preferred recipient / Mailable once you confirm mail is configured.
        Mail::raw(
            "New enquiry from {$data['first_name']} {$data['last_name']} ({$data['email']}, {$data['phone']}):\n\n{$data['message']}",
            fn ($m) => $m->to('support@easygets.ng')->subject('New EasyGets Contact Enquiry')
        );

        return back()->with('success', 'Thanks! Your enquiry has been received — we\'ll get back to you shortly.');
    }
}