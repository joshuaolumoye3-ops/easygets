<?php

use App\Services\MoreThanPanelService;

if (!function_exists('smm_api')) {
    function smm_api()
    {
        $api = new MoreThanPanelService();
        $api->api_key = env('MTP_API_KEY');
        return $api;
    }
}
