<?php

namespace App\Console\Commands;

use App\Helpers\PrestmitHelper;
use Illuminate\Console\Command;

class GiftcardsCheck extends Command
{
    protected $signature = 'giftcards:check {--sku= : Optional buy SKU to test POST signing} {--price=10}';
    protected $description = 'Verify Prestmit credentials and find the API-Hash variant your account accepts.';

    public function handle(): int
    {
        $c = config('services.prestmit');
        $this->info('Base URL: ' . $c['base_url']);
        $this->line('API key: ' . (!empty($c['api_key']) ? 'set' : 'MISSING') . ' | secret: ' . (!empty($c['api_secret']) ? 'set' : 'MISSING')
            . ' | webhook secret: ' . (!empty($c['webhook_secret']) ? 'set' : 'MISSING') . ' | account PIN: ' . (!empty($c['account_pin']) ? 'set' : 'MISSING'));

        $this->newLine();
        $this->info('GET /general/service-availability with each empty-body hash variant:');
        foreach (['[]', '{}', ''] as $variant) {
            config(['services.prestmit.empty_hash_body' => $variant]);
            $r = (new PrestmitHelper())->request('GET', 'general/service-availability');
            $this->line(sprintf('  empty body %-4s → HTTP %d %s', $variant === '' ? '""' : $variant, $r['status'], $r['ok'] ? 'OK ✔  → set PRESTMIT_EMPTY_HASH_BODY=' . $variant : '✘ ' . $r['error']));
        }

        if ($sku = $this->option('sku')) {
            $this->newLine();
            $this->info('POST /buy/check-availability with each typing variant:');
            foreach ([false, true] as $numeric) {
                config(['services.prestmit.hash_numeric' => $numeric]);
                $r = (new PrestmitHelper())->request('POST', 'giftcard-trade/buy/check-availability', [
                    'giftCardSKU' => (int) $sku, 'price' => (int) $this->option('price'), 'quantity' => 1,
                ]);
                $this->line(sprintf('  PRESTMIT_HASH_NUMERIC=%-5s → HTTP %d %s', $numeric ? 'true' : 'false', $r['status'], $r['ok'] ? 'OK ✔' : '✘ ' . $r['error']));
            }
        }
        return self::SUCCESS;
    }
}