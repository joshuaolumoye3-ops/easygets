<?php

namespace Tests\Unit;

use App\Helpers\DemmyPayHelper;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AfrikVendApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.afrikvend.base_url' => 'https://api.afrikvend.com',
            'services.afrikvend.api_key' => 'test-api-key',
        ]);
    }

    public function test_airtime_uses_network_endpoint_api_key_and_e164_phone(): void
    {
        Http::fake([
            'api.afrikvend.com/vend/mtn/airtime' => Http::response([
                'message' => 'Success',
                'status' => 'processed',
                'code' => 'SUC200',
                'txRef' => 'airtime-ref',
            ], 200),
        ]);

        $response = DemmyPayHelper::buyAirtime('MTN', 100, '08012345678', 'airtime-ref');

        $this->assertTrue(DemmyPayHelper::isSuccessful($response));
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://api.afrikvend.com/vend/mtn/airtime'
            && $request->hasHeader('x-api-key', 'test-api-key')
            && $request['phone_number'] === '2348012345678'
            && (float) $request['amount'] === 100.0
            && $request['reference'] === 'airtime-ref'
        );
    }

    public function test_data_uses_tariff_type_id_and_network_endpoint(): void
    {
        Http::fake([
            'api.afrikvend.com/vend/airtel/data' => Http::response([
                'message' => 'Success',
                'status' => 'processed',
                'code' => 'SUC200',
                'txRef' => 'data-ref',
            ], 200),
        ]);

        DemmyPayHelper::buyData('airtel', '2348012345678', 1500, 'plan-42', 'data-ref');

        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://api.afrikvend.com/vend/airtel/data'
            && $request['phone_number'] === '2348012345678'
            && (float) $request['amount'] === 1500.0
            && $request['tariff_type_id'] === 'plan-42'
            && $request['reference'] === 'data-ref'
        );
    }

    public function test_rejects_invalid_phones_and_non_success_envelopes(): void
    {
        $this->assertNull(DemmyPayHelper::normalizeNigerianPhone('123456'));
        $this->assertFalse(DemmyPayHelper::isSuccessful([
            '_http_status' => 200,
            'status' => 'failed',
            'code' => 'ERR400',
        ]));
        $this->assertFalse(DemmyPayHelper::isSuccessful([
            '_http_status' => 503,
            'status' => 'processed',
            'code' => 'SUC200',
        ]));
    }
}