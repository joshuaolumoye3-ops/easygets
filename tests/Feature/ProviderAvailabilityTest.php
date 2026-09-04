<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProviderAvailabilityTest extends TestCase
{
    public function test_sms_provider_config_entries_are_present_for_dashboard_checks(): void
    {
        $services = require __DIR__ . '/../../config/services.php';

        $this->assertArrayHasKey('smsbower', $services);
        $this->assertArrayHasKey('smsbus', $services);
        $this->assertArrayHasKey('api_key', $services['smsbower']);
        $this->assertArrayHasKey('token', $services['smsbus']);

        $providerVisible = function (?array $provider): bool {
            if (!$provider) {
                return false;
            }

            return strtolower((string) ($provider['status'] ?? '')) === 'active'
                && ($provider['has_active_products'] ?? false) === true;
        };

        $this->assertTrue($providerVisible([
            'status' => 'active',
            'has_active_products' => true,
        ]));
    }
}
