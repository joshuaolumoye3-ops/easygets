<?php

namespace Tests\Unit;

use App\Helpers\OtpProviderResponseHelper;
use PHPUnit\Framework\TestCase;

class OtpProviderResponseHelperTest extends TestCase
{
    public function test_it_extracts_5sim_activation_from_nested_payload(): void
    {
        $payload = [
            'data' => [
                'id' => 123456,
                'phone' => '+2348012345678',
            ],
        ];

        $result = OtpProviderResponseHelper::extractActivationData($payload, '5sim');

        $this->assertSame('123456', $result['request_id']);
        $this->assertSame('+2348012345678', $result['phone']);
    }

    public function test_it_extracts_smsbus_activation_from_nested_payload(): void
    {
        $payload = [
            'code' => 200,
            'data' => [
                'request_id' => 987654,
                'number' => '+2348098765432',
            ],
        ];

        $result = OtpProviderResponseHelper::extractActivationData($payload, 'smsbus');

        $this->assertSame('987654', $result['request_id']);
        $this->assertSame('+2348098765432', $result['phone']);
    }

    public function test_it_extracts_smsbower_activation_from_access_number_response(): void
    {
        $result = OtpProviderResponseHelper::extractActivationData('ACCESS_NUMBER:112233:+2348123456789', 'smsbower');

        $this->assertSame('112233', $result['request_id']);
        $this->assertSame('+2348123456789', $result['phone']);
    }
}
