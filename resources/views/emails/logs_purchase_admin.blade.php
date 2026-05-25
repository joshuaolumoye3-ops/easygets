<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>New Login Logs Order</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7fafc; padding: 24px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 16px 32px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color: #03624C; padding: 24px; color: #ffffff; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px;">New Logs Purchase</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 16px;">Hello Team,</p>
                            <p style="margin: 0 0 16px; font-size: 16px;">A new logs order was completed by <strong>{{ $user->username ?? $user->email }}</strong>.</p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0; font-weight: bold;">Transaction Reference</td>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0;">{{ $transaction->reference }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;">User</td>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0;">{{ $user->username ?? $user->email }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0; font-weight: bold;">Product</td>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0;">{{ $product->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;">Country</td>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0;">{{ $product->country ?? 'Global' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0; font-weight: bold;">Price</td>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0;">₦{{ number_format($transaction->amount ?? 0, 2) }}</td>
                                </tr>
                                @if(!empty($product->description))
                                    <tr>
                                        <td style="padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;">Details</td>
                                        <td style="padding: 12px; border: 1px solid #e2e8f0;">{{ $product->description }}</td>
                                    </tr>
                                @endif
                            </table>

                            <p style="margin: 24px 0 0; font-size: 16px;">Please review this logs order, mark it fulfilled once the credentials are ready, and update the user history accordingly.</p>
                            <p style="margin: 16px 0 0; font-size: 16px;">Regards,<br>The EasyGets Team</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
