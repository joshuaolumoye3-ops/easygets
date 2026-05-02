/**
 * JAMB e-PIN Integration Examples
 * These examples demonstrate complete JAMB e-PIN purchase flows
 * 
 * Includes:
 * - Valid purchase flow
 * - Error handling scenarios
 * - Profile verification
 * - Security features explanation
 */

// ============================================
// EXAMPLE 1: COMPLETE VALID PURCHASE FLOW
// ============================================

async function completePurchaseFlow() {
    // Step 1: User selects exam type
    const examType = 'utme-mock'; // or 'utme-no-mock', 'de'
    const phoneNumber = '08123456789';
    const amount = 3500; // Automatically set based on exam type
    
    // Step 2: Frontend validation
    if (!isValidPhoneNumber(phoneNumber)) {
        showError('Invalid phone number format. Use: 08123456789');
        return;
    }
    
    if (walletBalance < amount) {
        showError(`Insufficient balance. Need ₦${amount}, have ₦${walletBalance}`);
        return;
    }
    
    // Step 3: Show PIN modal to user
    showPinModal(`
        Exam Type: ${examType}
        Phone: ${phoneNumber}
        Amount: ₦${amount}
        Balance: ₦${walletBalance}
    `);
    
    // Step 4: User enters PIN and submits
    // (Handled by form submission in jambPin.blade.php)
    
    // Step 5: Backend processes purchase
    const response = await fetch('/jamb-pin/purchase', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            exam_type: examType,
            phone_number: phoneNumber,
            amount: amount,
            pin: userEnteredPin,
            profile_id: profileId || null
        })
    });
    
    const result = await response.json();
    
    // Step 6: Handle response
    if (result.success) {
        // SUCCESS - Show transaction details
        displaySuccess({
            message: 'JAMB e-PIN purchased successfully!',
            details: `
                Reference: ${result.transaction.reference}
                Card Details: ${result.transaction.card_details}
                New Balance: ₦${result.transaction.new_balance}
                Time: ${result.transaction.timestamp}
            `
        });
    } else {
        // ERROR - Show user-friendly message
        displayError({
            title: result.title,
            message: result.message,
            suggestion: result.suggestion
        });
    }
}

function isValidPhoneNumber(phone) {
    // Nigerian format: 08X-XXXXXXXXX
    const regex = /^0[7-9]\d{9}$/;
    return regex.test(phone);
}


// ============================================
// EXAMPLE 2: INVALID PIN ERROR
// ============================================

async function handleInvalidPin() {
    const pin = '0000'; // User enters wrong PIN
    
    const response = await fetch('/jamb-pin/purchase', {
        method: 'POST',
        body: JSON.stringify({
            exam_type: 'utme-mock',
            phone_number: '08123456789',
            amount: 3500,
            pin: pin,  // ← Wrong PIN
            _token: csrfToken
        })
    });
    
    // Expected Response: 422 Validation Error
    const result = await response.json();
    
    console.log(result);
    /**
     * {
     *   "success": false,
     *   "error_type": "validation",
     *   "title": "🔐 INVALID PIN",
     *   "message": "Invalid PIN. Please try again.",
     *   "details": "ℹ️ Please enter your 4-digit transaction PIN.",
     *   "suggestion": "If you forgot your PIN, visit Settings → Security to reset it."
     * }
     */
    
    // User sees professional error modal
    window.handlePurchaseError(result);
    
    // Important: Wallet NOT deducted
    // Important: No transaction created
    // User can try again
}


// ============================================
// EXAMPLE 3: INSUFFICIENT BALANCE
// ============================================

async function handleInsufficientBalance() {
    const userBalance = 500.00; // Not enough
    const requiredAmount = 3500.00;
    const shortfall = requiredAmount - userBalance;
    
    // User tries to purchase
    const response = await fetch('/jamb-pin/purchase', {
        method: 'POST',
        body: JSON.stringify({
            exam_type: 'utme-mock',
            phone_number: '08123456789',
            amount: 3500,
            pin: userPin,
            _token: csrfToken
        })
    });
    
    // Expected Response: 400 Bad Request
    const result = await response.json();
    
    console.log(result);
    /**
     * {
     *   "success": false,
     *   "error_type": "insufficient_balance",
     *   "title": "💰 INSUFFICIENT BALANCE",
     *   "message": "Your wallet balance is not enough for this purchase.",
     *   "details": "Required: ₦3,500.00 | Available: ₦500.00 | Shortfall: ₦3,000.00",
     *   "suggestion": "Please fund your wallet with at least ₦3,000.00"
     * }
     */
    
    window.handlePurchaseError(result);
    
    // Important: Stop before PIN verification
    // Important: Wallet NOT deducted
    // Important: User sees clear guidance
}


// ============================================
// EXAMPLE 4: SERVICE ERROR (API DOWN)
// ============================================

async function handleServiceError() {
    // User has valid data, sufficient balance, correct PIN
    // But ClubKonnect API is down or returns error
    
    const response = await fetch('/jamb-pin/purchase', {
        method: 'POST',
        body: JSON.stringify({
            exam_type: 'utme-mock',
            phone_number: '08123456789',
            amount: 3500,
            pin: '1234',
            _token: csrfToken
        })
    });
    
    // Backend deducted funds
    // Called ClubKonnect API
    // API returned error
    // Backend automatically refunded funds
    
    // Expected Response: 503 Service Unavailable
    const result = await response.json();
    
    console.log(result);
    /**
     * {
     *   "success": false,
     *   "error_type": "service_error",
     *   "title": "⚠️ PURCHASE FAILED",
     *   "message": "We're experiencing difficulties processing your request. 
     *                Your funds have been refunded.",
     *   "details": "API service temporarily unavailable",
     *   "suggestion": "Please try again in a few moments. Your wallet balance was not affected.",
     *   "refunded": true,
     *   "fund_refunded": 3500
     * }
     */
    
    window.handlePurchaseError(result);
    
    // Important: Funds were deducted
    // Important: Funds were automatically refunded
    // Important: User sees reassurance message
    // Important: User can retry
}


// ============================================
// EXAMPLE 5: SUCCESSFUL PURCHASE
// ============================================

async function handleSuccessfulPurchase() {
    const response = await fetch('/jamb-pin/purchase', {
        method: 'POST',
        body: JSON.stringify({
            exam_type: 'utme-mock',
            phone_number: '08149659347',
            amount: 3500,
            pin: '1234',
            _token: csrfToken
        })
    });
    
    // Backend:
    // 1. Validated all inputs ✓
    // 2. Verified PIN ✓
    // 3. Locked wallet ✓
    // 4. Checked balance ✓
    // 5. Deducted funds ✓
    // 6. Called ClubKonnect API ✓
    // 7. API returned success ✓
    // 8. Saved transaction ✓
    // 9. Committed to database ✓
    
    // Expected Response: 200 OK
    const result = await response.json();
    
    console.log(result);
    /**
     * {
     *   "success": true,
     *   "status": "success",
     *   "message": "Your JAMB e-PIN has been successfully purchased!",
     *   "transaction": {
     *     "reference": "JAMB-ABG123KLM-1234567890",
     *     "order_id": "6501321715",
     *     "exam_type": "utme-mock",
     *     "phone": "08149659347",
     *     "amount": 3500,
     *     "card_details": "Serial No:WRN200343867, pin: 572871474684",
     *     "status": "Success",
     *     "new_balance": 6500,
     *     "timestamp": "2:30 PM"
     *   }
     * }
     */
    
    // Show success modal with transaction details
    window.handlePurchaseSuccess(result);
    
    // User sees:
    // - ✅ Purchase successful message
    // - 📱 JAMB e-PIN card details (will be sent to phone)
    // - 💾 Transaction reference for records
    // - 💰 New wallet balance
    // - ⏰ Transaction timestamp
}


// ============================================
// EXAMPLE 6: PROFILE VERIFICATION (Optional)
// ============================================

async function verifyJambProfile() {
    const examType = 'utme-mock';
    const profileId = '1234567890'; // User's JAMB profile ID
    
    // Show loading state
    const verifyBtn = document.getElementById('verifyBtn');
    verifyBtn.disabled = true;
    verifyBtn.textContent = '⏳ Verifying...';
    
    try {
        const response = await fetch('/jamb-pin/verify-profile', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                exam_type: examType,
                profile_id: profileId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Profile verified
            console.log('Verified as:', result.name);
            // "BALOGUN SUNDAY (BKwise)"
            
            // Show success
            document.getElementById('verifyResult').textContent = 
                '✅ ' + result.name;
            document.getElementById('verifyResult').className = 
                'text-green-600';
        } else {
            // Profile not found or invalid
            console.log('Verification failed:', result.message);
            
            // Show error
            document.getElementById('verifyResult').textContent = 
                '❌ ' + result.message;
            document.getElementById('verifyResult').className = 
                'text-red-600';
        }
    } catch (error) {
        console.error('Verification error:', error);
        document.getElementById('verifyResult').textContent = 
            '⚠️ Verification service unavailable';
        document.getElementById('verifyResult').className = 
            'text-gray-600';
    } finally {
        verifyBtn.disabled = false;
        verifyBtn.textContent = '✓ Verify';
    }
}


// ============================================
// SECURITY FEATURES EXPLANATION
// ============================================

const securityFeatures = {
    
    // 1. Database Transactions
    databaseTransactions: {
        description: 'All-or-nothing operations',
        benefit: 'If any step fails, entire transaction is rolled back',
        example: 'If API fails after deducting funds, funds are refunded automatically',
        code: `
            DB::beginTransaction();
            try {
                $wallet->decrement('balance', $amount);
                $response = ClubKonnectHelper::buyJamb(...);
                if ($failure) {
                    $wallet->increment('balance', $amount); // Refund
                    DB::rollBack();
                } else {
                    DB::commit();
                }
            } catch (Exception $e) {
                DB::rollBack(); // Undo everything
            }
        `
    },
    
    // 2. Row-Level Locking
    rowLocking: {
        description: 'Prevents concurrent access to wallet',
        benefit: 'Only one purchase can happen at a time for a user',
        example: 'If user submits form twice quickly, only one succeeds',
        code: `
            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate() // LOCK FOR UPDATE
                ->first();
        `
    },
    
    // 3. Input Validation
    inputValidation: {
        description: 'Validates all user inputs on server',
        benefit: 'Prevents injection attacks and invalid data',
        validations: [
            'Phone format: 08X-XXXXXXXXX',
            'Amount: numeric, within min/max',
            'PIN: exactly 4 digits',
            'Exam type: only valid options'
        ]
    },
    
    // 4. Automatic Refunds
    autoRefunds: {
        description: 'Funds returned on any failure',
        benefit: 'User funds are never stuck',
        scenarios: [
            'API service down → Automatic refund',
            'Network timeout → Automatic refund',
            'Invalid profile → No deduction',
            'Invalid PIN → No deduction'
        ]
    },
    
    // 5. CSRF Protection
    csrfProtection: {
        description: 'All POST requests require CSRF token',
        benefit: 'Prevents cross-site request forgery attacks',
        code: `
            // Required in all forms
            @csrf
            
            // Required in all AJAX requests
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        `
    },
    
    // 6. PIN Verification
    pinVerification: {
        description: 'User must enter 4-digit PIN to confirm purchase',
        benefit: 'Prevents accidental or unauthorized purchases',
        flow: [
            'User fills form',
            'Modal shows purchase summary',
            'User enters PIN',
            'Server verifies PIN',
            'If correct: Process purchase',
            'If wrong: Show error, allow retry'
        ]
    },
    
    // 7. Credential Masking
    credentialMasking: {
        description: 'Sensitive data masked in logs',
        benefit: 'API keys and PINs never exposed in logs',
        examples: {
            'phone': '08123456789 → 081****6789',
            'pin': '1234 → ****',
            'apiKey': 'XXXXX...YYY (first/last chars only)'
        }
    },
    
    // 8. Transaction Audit Trail
    auditTrail: {
        description: 'All transactions logged with details',
        benefit: 'Can trace any issue back to root cause',
        logged: [
            'User ID',
            'Amount',
            'Status (success/failed/pending)',
            'Request/Order ID',
            'Timestamp',
            'Error details (if any)',
            'API response',
            'Wallet balance before/after'
        ]
    }
};


// ============================================
// PRODUCTION CHECKLIST
// ============================================

const productionChecklist = {
    
    codeReview: [
        'All error messages are user-friendly (no technical jargon)',
        'Sensitive data is masked in logs',
        'Database queries use prepared statements',
        'CSRF tokens are required for all POST requests',
        'Input validation is on backend, not just frontend'
    ],
    
    security: [
        'HTTPS enabled on all endpoints',
        'API credentials in environment variables only',
        'No hardcoded secrets in code',
        'Rate limiting enabled on purchase endpoint',
        'PIN hashing in database'
    ],
    
    database: [
        'Transactions table has proper indexes',
        'Wallet table has row-level locking',
        'Database backups configured',
        'Migration scripts tested'
    ],
    
    logging: [
        'Log rotation configured',
        'Error monitoring (Sentry/Rollbar) set up',
        'Daily log review process',
        'Storage space monitored'
    ],
    
    testing: [
        'All 4 error scenarios tested',
        'Successful purchase tested',
        'Profile verification tested',
        'Concurrent requests tested',
        'Mobile responsive tested',
        'API throttling tested'
    ],
    
    monitoring: [
        'Error rate monitoring',
        'API latency monitoring',
        'Wallet balance reconciliation',
        'Transaction success rate monitoring',
        'User support ticket tracking'
    ],
    
    documentation: [
        'Admin documentation for troubleshooting',
        'Support team runbook created',
        'API change logs maintained',
        'Incident response procedures documented'
    ]
};

console.log('JAMB e-PIN Integration Examples loaded');
console.log('See examples above for complete implementation patterns');
