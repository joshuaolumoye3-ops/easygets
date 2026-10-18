<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
    <tr>
        <td style="background:#03624C;padding:18px 24px;color:#ffffff;font-size:16px;font-weight:700;">
            Withdrawal needs your attention
        </td>
    </tr>
    <tr>
        <td style="padding:24px;font-size:14px;line-height:1.6;">
            <p style="margin:0 0 12px;"><strong>{{ $ctx['reason'] }}</strong></p>

            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;font-size:13px;">
                <tr><td style="color:#6b7280;">Reference</td><td style="text-align:right;font-family:monospace;">{{ $ctx['reference'] }}</td></tr>
                <tr><td style="color:#6b7280;">Amount</td><td style="text-align:right;"><strong>₦{{ number_format($ctx['amount'], 2) }}</strong></td></tr>
                <tr><td style="color:#6b7280;">Beneficiary</td><td style="text-align:right;">{{ $ctx['bank_name'] }} · {{ $ctx['account_tail'] }}</td></tr>
                <tr><td style="color:#6b7280;">Attempts</td><td style="text-align:right;">{{ $ctx['attempts'] }}</td></tr>
                <tr><td style="color:#6b7280;">Flutterwave NGN balance</td><td style="text-align:right;">{{ $ctx['balance'] !== null ? '₦' . number_format($ctx['balance'], 2) : 'unavailable' }}</td></tr>
                <tr><td style="color:#6b7280;">All pending withdrawals</td><td style="text-align:right;">{{ $ctx['pending_count'] }} · ₦{{ number_format($ctx['pending_sum'], 2) }}</td></tr>
            </table>

            @if ($ctx['code'] === 'insufficient_funds')
                <p style="margin:16px 0 0;">Fund your Flutterwave NGN balance, then open the admin gift-card page and click <strong>Process</strong> on the pending withdrawal(s). Nothing was sent to the user's bank and their balance is already reserved.</p>
            @elseif ($ctx['code'] === 'outcome_unknown')
                <p style="margin:16px 0 0;">Flutterwave did not confirm whether this transfer was created. <strong>Do not retry yet.</strong> Click <strong>Sync status</strong> on the withdrawal; the webhook normally resolves it within minutes.</p>
            @else
                <p style="margin:16px 0 0;">Please review this withdrawal in the admin dashboard.</p>
            @endif

            <p style="margin:20px 0 0;">
                <a href="{{ $ctx['admin_url'] }}" style="display:inline-block;background:#03624C;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:600;">Open admin dashboard</a>
            </p>
        </td>
    </tr>
</table>
</body>
</html>