<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>eSIM Order Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7fafc; padding: 24px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 16px 32px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color: #03624C; padding: 24px; color: #ffffff; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px;">Your eSIM Purchase is Confirmed</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px; font-size: 16px;">Hello {{ $user->first_name ?? $user->username }},</p>
                            <p style="margin: 0 0 16px; font-size: 16px;">Your eSIM purchase has been successfully completed. Below are the order details:</p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0; font-weight: bold;">Transaction Reference</td>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0;">{{ $transaction->reference }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;">Country</td>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0;">{{ $country->country_name ?? ($country['title'] ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0; font-weight: bold;">Plan</td>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0;">{{ $package->plan ?? ($package['title'] ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0; font-weight: bold;">Validity</td>
                                    <td style="padding: 12px; border: 1px solid #e2e8f0;">{{ $package->validity_days ?? ($package['validity'] ?? 'N/A') }} days</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0; font-weight: bold;">Amount</td>
                                    <td style="padding: 12px; background: #f3f6f9; border: 1px solid #e2e8f0;">₦{{ number_format($transaction->amount ?? ($package->price ?? 0), 2) }}</td>
                                </tr>
                            </table>

                            <p style="margin: 24px 0 0; font-size: 16px;">If you have any questions, please reply to this email or contact support.</p>
                            <p style="margin: 16px 0 0; font-size: 16px;">Thank you for choosing EasyGets.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
