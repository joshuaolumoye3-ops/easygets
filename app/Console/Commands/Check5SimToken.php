<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class Check5SimToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:5sim';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify configured 5sim token by calling the profile endpoint (masked output)';

    public function handle()
    {
        $token = config('services.5sim.token') ?: env('FIVESIM_TOKEN');

        $mask = is_string($token) && strlen($token) > 8
            ? substr($token, 0, 6) . '...' . substr($token, -4)
            : '***';

        $this->info('Configured token (masked): ' . $mask);

        if (!$token) {
            $this->error('No token found in config/services.php or FIVESIM_TOKEN env.');
            return 2;
        }

        try {
            $resp = Http::timeout(15)
                ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                ->get('https://5sim.net/v1/user/profile');

            $status = $resp->status();
            $this->info('HTTP status: ' . $status);

            if ($resp->successful()) {
                $json = $resp->json();
                $display = $json['login'] ?? $json['email'] ?? json_encode(array_slice($json, 0, 3));
                $this->info('Token valid. Profile summary: ' . $display);
                return 0;
            }

            if (in_array($status, [401, 403]) || trim($resp->body()) === '') {
                $this->error('Auth rejected (status ' . $status . '). Token invalid/expired or account blocked/insufficient balance.');
                return 3;
            }

            $this->error('Unexpected response (status ' . $status . '): ' . substr($resp->body(), 0, 500));
            return 4;
        } catch (\Exception $e) {
            $this->error('Request failed: ' . $e->getMessage());
            return 5;
        }
    }
}
