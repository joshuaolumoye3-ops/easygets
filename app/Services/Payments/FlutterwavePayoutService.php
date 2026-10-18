<?php

namespace App\Services\Payments;

use App\Mail\PayoutNeedsAttention;
use App\Models\GiftcardWithdrawal;
use App\Services\Flutterwave\FlutterwaveClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FlutterwavePayoutService
{
	public function __construct(private FlutterwaveClient $flw)
	{
	}

	public function process(int $id, ?int $adminId = null): array
	{
		$lock = Cache::lock('flw-payout:' . $id, 90);
		if (!$lock->get()) {
			return $this->result('processing', 'This payout is already being processed.');
		}

		try {
			$withdrawal = GiftcardWithdrawal::findOrFail($id);
			if ($withdrawal->status !== 'pending') {
				return $this->result($withdrawal->status, 'Only pending withdrawals can be processed.');
			}

			$found = $this->findByReference($withdrawal->reference);
			if ($found['ok'] && $found['transfer']) {
				return $this->applyProviderTransfer($withdrawal, $found['transfer'], $adminId);
			}
			if (!$found['ok']) {
				return $this->needsAttention($withdrawal, $found['message'], 'outcome_unknown', null, $adminId);
			}

			$balance = $this->flw->balance('NGN');
			$required = (float) $withdrawal->amount + (float) config('services.flutterwave.payout_fee_buffer', 100);
			if (!$balance['ok']) {
				return $this->needsAttention($withdrawal, 'Flutterwave NGN balance could not be checked: ' . $balance['message'], 'provider_error', null, $adminId);
			}
			if ($balance['available'] < $required) {
				$reason = 'Flutterwave NGN balance is ₦' . number_format((float) $balance['available'], 2)
					. '; approximately ₦' . number_format($required, 2) . ' is required including the fee buffer.';
				return $this->needsAttention($withdrawal, $reason, 'insufficient_funds', $balance['available'], $adminId);
			}

			$transfer = $this->flw->transfer([
				'account_bank' => $withdrawal->bank_code,
				'account_number' => $withdrawal->account_number,
				'amount' => (float) $withdrawal->amount,
				'currency' => 'NGN',
				'reference' => $withdrawal->reference,
				'narration' => 'Gift card withdrawal ' . $withdrawal->reference,
				'debit_currency' => 'NGN',
			]);

			if (!$transfer['ok']) {
				$code = $transfer['ambiguous'] ? 'outcome_unknown' : 'provider_error';
				return $this->needsAttention($withdrawal, $transfer['message'], $code, $balance['available'], $adminId);
			}

			return $this->applyProviderTransfer($withdrawal, (array) $transfer['data'], $adminId);
		} finally {
			$lock->release();
		}
	}

	public function sync(GiftcardWithdrawal $withdrawal, ?int $adminId = null): array
	{
		$withdrawal->refresh();
		if (in_array($withdrawal->status, ['approved', 'declined'], true)) {
			return $this->result($withdrawal->status, 'This withdrawal is already complete.');
		}

		$found = $withdrawal->provider_transfer_id
			? $this->transferById($withdrawal->provider_transfer_id)
			: $this->findByReference($withdrawal->reference);

		if (!$found['ok']) {
			return $this->result($withdrawal->status, $found['message']);
		}
		if (!$found['transfer']) {
			return $this->result($withdrawal->status, 'Flutterwave has no transfer record for this withdrawal yet.');
		}

		return $this->applyProviderTransfer($withdrawal, $found['transfer'], $adminId);
	}

	public function applyTransferEvent(array $event): array
	{
		if (empty($event['id'])) {
			return $this->result('ignored', 'Transfer event has no provider ID.');
		}

		$verified = $this->transferById($event['id']);
		if (!$verified['ok'] || !$verified['transfer']) {
			throw new \RuntimeException('Flutterwave transfer verification unavailable.');
		}

		$reference = (string) ($verified['transfer']['reference'] ?? '');
		if ($reference === '') {
			return $this->result('unmatched', 'Transfer has no reference.');
		}

		$withdrawal = GiftcardWithdrawal::where('reference', $reference)
			->where('provider', 'flutterwave')->first();
		if (!$withdrawal) {
			return $this->result('unmatched', 'No withdrawal matches this transfer.');
		}

		return $this->applyProviderTransfer($withdrawal, $verified['transfer']);
	}

	public function decline(int $id, ?int $adminId, string $reason): array
	{
		$withdrawal = GiftcardWithdrawal::findOrFail($id);
		if ($withdrawal->status !== 'pending') {
			return $this->result($withdrawal->status, 'Only pending withdrawals can be declined.');
		}

		if ($withdrawal->provider_transfer_id) {
			$transfer = $this->transferById($withdrawal->provider_transfer_id);
			if (!$transfer['ok'] || !$transfer['transfer']) {
				return $this->result('pending', 'Flutterwave status is unknown; sync the transfer before refunding.');
			}
			$providerStatus = strtolower((string) ($transfer['transfer']['status'] ?? ''));
			if (!in_array($providerStatus, ['failed', 'failure', 'rejected'], true)) {
				return $this->result($providerStatus === 'successful' ? 'approved' : 'processing', 'A transfer may still be in flight and cannot be refunded yet.');
			}
		}

		return DB::transaction(function () use ($id, $adminId, $reason) {
			$withdrawal = GiftcardWithdrawal::lockForUpdate()->findOrFail($id);
			if ($withdrawal->status !== 'pending') {
				return $this->result($withdrawal->status, 'This withdrawal cannot be declined while a transfer may be in flight.');
			}

			$this->refundAndDecline($withdrawal, $reason, $adminId);
			return $this->result('declined', 'Withdrawal declined and funds returned to the user.');
		});
	}

	private function findByReference(string $reference): array
	{
		$response = $this->flw->findTransfersByReference($reference);
		if (!$response['ok']) {
			return ['ok' => false, 'transfer' => null, 'message' => $response['message']];
		}

		$data = $response['data'];
		$transfers = is_array($data) && array_is_list($data) ? $data : (is_array($data) ? [$data] : []);
		foreach ($transfers as $transfer) {
			if (is_array($transfer) && (string) ($transfer['reference'] ?? '') === $reference) {
				return ['ok' => true, 'transfer' => $transfer, 'message' => 'OK'];
			}
		}

		return ['ok' => true, 'transfer' => null, 'message' => 'Not found'];
	}

	private function transferById(int|string $id): array
	{
		$response = $this->flw->getTransfer($id);
		return [
			'ok' => $response['ok'],
			'transfer' => $response['ok'] && is_array($response['data']) ? $response['data'] : null,
			'message' => $response['message'],
		];
	}

	private function applyProviderTransfer(GiftcardWithdrawal $withdrawal, array $transfer, ?int $adminId = null): array
	{
		if ((string) ($transfer['reference'] ?? '') !== (string) $withdrawal->reference) {
			return $this->result($withdrawal->status, 'Flutterwave transfer reference did not match.');
		}

		$status = strtolower((string) ($transfer['status'] ?? ''));
		$transferId = isset($transfer['id']) ? (string) $transfer['id'] : null;

		if (in_array($status, ['successful', 'success', 'completed'], true)) {
			DB::transaction(function () use ($withdrawal, $transferId, $adminId) {
				$row = GiftcardWithdrawal::lockForUpdate()->findOrFail($withdrawal->id);
				if (in_array($row->status, ['approved', 'declined'], true)) {
					return;
				}
				$row->forceFill([
					'status' => 'approved', 'provider' => 'flutterwave',
					'provider_transfer_id' => $transferId, 'failure_reason' => null,
					'admin_id' => $adminId ?? $row->admin_id, 'processed_at' => now(),
				])->save();
			});
			return $this->result('approved', 'Flutterwave confirmed the payout.');
		}

		if (in_array($status, ['failed', 'failure', 'rejected'], true)) {
			$reason = trim((string) ($transfer['complete_message'] ?? $transfer['message'] ?? 'Flutterwave reported a failed transfer.'));
			if ($this->isAccountRejection($reason)) {
				DB::transaction(function () use ($withdrawal, $reason, $transferId) {
					$row = GiftcardWithdrawal::lockForUpdate()->findOrFail($withdrawal->id);
					if (!in_array($row->status, ['approved', 'declined'], true)) {
						$row->forceFill(['provider_transfer_id' => $transferId])->save();
						$this->refundAndDecline($row, $reason, null);
					}
				});
				return $this->result('declined', 'Bank rejected the account; the withdrawal was refunded.');
			}

			return $this->needsAttention($withdrawal, $reason, 'provider_error', null, $adminId, $transferId);
		}

		GiftcardWithdrawal::whereKey($withdrawal->id)->whereNotIn('status', ['approved', 'declined'])
			->update([
				'status' => 'processing', 'provider' => 'flutterwave', 'provider_transfer_id' => $transferId,
				'failure_reason' => null, 'updated_at' => now(),
			]);

		return $this->result('processing', 'Flutterwave is processing the transfer.');
	}

	private function needsAttention(
		GiftcardWithdrawal $withdrawal,
		string $reason,
		string $code,
		?float $balance,
		?int $adminId = null,
		?string $transferId = null
	): array {
		$withdrawal->refresh();
		$attempts = (int) $withdrawal->payout_attempts + 1;
		$withdrawal->forceFill([
			'status' => 'pending', 'provider' => 'flutterwave', 'failure_reason' => $reason,
			'provider_transfer_id' => $transferId ?? $withdrawal->provider_transfer_id,
			'payout_attempts' => $attempts, 'admin_id' => $adminId ?? $withdrawal->admin_id,
		])->save();

		$this->sendAttentionEmail($withdrawal->fresh(), $reason, $code, $balance, $attempts);
		return $this->result('pending', $reason);
	}

	private function sendAttentionEmail(GiftcardWithdrawal $withdrawal, string $reason, string $code, ?float $balance, int $attempts): void
	{
		$email = (string) config('services.flutterwave.admin_alert_email');
		if ($email === '' || $withdrawal->payout_alerted_at) {
			return;
		}

		$claimed = GiftcardWithdrawal::whereKey($withdrawal->id)->whereNull('payout_alerted_at')
			->update(['payout_alerted_at' => now(), 'updated_at' => now()]);
		if (!$claimed) {
			return;
		}

		try {
			Mail::to($email)->send(new PayoutNeedsAttention([
				'subject' => 'Gift-card withdrawal needs attention: ' . $withdrawal->reference,
				'reason' => $reason,
				'reference' => $withdrawal->reference,
				'amount' => (float) $withdrawal->amount,
				'bank_name' => $withdrawal->bank_name,
				'account_tail' => FlutterwaveClient::maskAccount((string) $withdrawal->account_number),
				'attempts' => $attempts,
				'balance' => $balance,
				'pending_count' => GiftcardWithdrawal::where('status', 'pending')->count(),
				'pending_sum' => (float) GiftcardWithdrawal::where('status', 'pending')->sum('amount'),
				'code' => $code,
				'admin_url' => route('admin.giftcards.index', ['tab' => 'withdrawals']),
			]));
		} catch (\Throwable $e) {
			Log::error('Gift-card payout alert email failed', ['withdrawal' => $withdrawal->id, 'error' => $e->getMessage()]);
		}
	}

	private function refundAndDecline(GiftcardWithdrawal $withdrawal, string $reason, ?int $adminId): void
	{
		DB::table('users')->where('id', $withdrawal->user_id)->lockForUpdate()->first();
		DB::table('users')->where('id', $withdrawal->user_id)->increment('withdrawable_balance', $withdrawal->amount);
		$withdrawal->forceFill([
			'status' => 'declined', 'failure_reason' => $reason, 'admin_note' => $reason,
			'admin_id' => $adminId ?? $withdrawal->admin_id, 'processed_at' => now(),
		])->save();
	}

	private function isAccountRejection(string $reason): bool
	{
		return (bool) preg_match(
			'/(?:invalid|incorrect|unknown|not found|does not exist|closed|blocked|rejected|not active).{0,40}(?:account|beneficiary|bank)|(?:account|beneficiary|bank).{0,40}(?:invalid|incorrect|unknown|not found|does not exist|closed|blocked|rejected|not active)/i',
			$reason
		);
	}

	private function result(string $status, string $message): array
	{
		return ['status' => $status, 'message' => $message];
	}
}
