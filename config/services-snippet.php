<?php

// Add this array to the existing return [...] in config/services.php,
// alongside your other provider blocks (e.g. 'flutterwave' => [...]).

'korapay' => [
    'public_key'        => env('KORAPAY_PUBLIC_KEY'),
    'secret_key'        => env('KORAPAY_SECRET_KEY'),
    'base_url'          => env('KORAPAY_BASE_URL', 'https://api.korapay.com/merchant/api/v1'),
    // Default settlement bank for newly created dedicated accounts.
    // See https://developers.korapay.com/docs/virtual-bank-accounts-ngn for the
    // current list of supported banks/codes. Use "000" only in sandbox.
    'default_bank_code' => env('KORAPAY_DEFAULT_BANK_CODE', '035'),
],
