/**
 * EXAMPLE: Complete Data Purchase Flow with Error Handling
 * 
 * This file shows how the entire purchase process works with proper
 * error handling and user feedback.
 */

// ============================================
// COMPLETE FORM SUBMISSION EXAMPLE
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Get the data purchase form
    const dataForm = document.querySelector('form[action="{{ route(\'data.purchase\') }}"]');
    
    if (!dataForm) {
        console.warn('Data form not found');
        return;
    }

    // Add submit listener
    dataForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        console.log('📤 Submitting data purchase form...');
        
        // Get submit button
        const submitBtn = dataForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // ============================================
        // STEP 1: VALIDATE FORM LOCALLY
        // ============================================
        
        const network = dataForm.querySelector('input[name="network"]').value;
        const dataPlan = dataForm.querySelector('input[name="data_plan"]').value;
        const recipient = dataForm.querySelector('input[name="recipient"]').value;
        const pin = dataForm.querySelector('input[name="pin"]').value;
        
        if (!network || !dataPlan || !recipient || !pin) {
            window.handlePurchaseError({
                title: 'Missing Information',
                message: 'Please fill in all required fields.',
                error_type: 'validation'
            });
            return;
        }
        
        // ============================================
        // STEP 2: SHOW LOADING STATE
        // ============================================
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing Purchase...
        `;
        
        try {
            
            // ============================================
            // STEP 3: SEND REQUEST TO SERVER
            // ============================================
            
            console.log('📡 Sending request to server...');
            
            const response = await fetch(dataForm.action, {
                method: 'POST',
                body: new FormData(dataForm),
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            // ============================================
            // STEP 4: PARSE RESPONSE
            // ============================================
            
            const result = await response.json();
            console.log('📥 Server response:', result);
            
            // ============================================
            // STEP 5: HANDLE RESPONSE
            // ============================================
            
            if (result.success) {
                
                // ✅ SUCCESS BRANCH
                console.log('✅ Purchase successful!');
                
                // Show success modal with transaction details
                window.handlePurchaseSuccess({
                    message: result.message,
                    data: result.data
                });
                
                // Reset form for next purchase
                setTimeout(() => {
                    dataForm.reset();
                    // Clear hidden inputs
                    document.querySelector('input[name="network"]').value = '';
                    document.querySelector('input[name="data_plan"]').value = '';
                    document.querySelector('input[name="pin"]').value = '';
                }, 2000);
                
                // Optional: Redirect to transactions page after 5 seconds
                setTimeout(() => {
                    // window.location.href = '/app/transactions';
                }, 5000);
                
            } else {
                
                // ❌ ERROR BRANCH
                console.error('❌ Purchase failed:', result);
                
                // Show user-friendly error modal
                window.handlePurchaseError(result);
                
                // Log for debugging
                if (result.details) {
                    console.table(result.details);
                }
            }
            
        } catch (error) {
            
            // ❌ NETWORK ERROR BRANCH
            console.error('🌐 Network error:', error);
            
            window.handlePurchaseError({
                title: 'Connection Error',
                message: 'Unable to connect to the server. Please check your internet connection and try again.',
                error_type: 'system_error',
                suggestion: 'Make sure you have a stable internet connection, then try again.'
            });
            
        } finally {
            
            // ============================================
            // STEP 6: RESTORE BUTTON STATE
            // ============================================
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            console.log('✓ Form ready for next submission');
        }
    });
});

// ============================================
// ERROR FLOW EXAMPLES
// ============================================

/*
 * EXAMPLE 1: USER ENTERS INVALID PIN
 * 
 * User Input:
 * - Network: MTN
 * - Plan: 500MB Monthly
 * - Phone: 08012345678
 * - PIN: 0000 (WRONG - correct is 1234)
 * 
 * Server Response:
 * {
 *   "success": false,
 *   "title": "Invalid PIN",
 *   "message": "Invalid PIN. Please try again.",
 *   "error_type": "validation",
 *   "suggestion": "Please enter your 4-digit transaction PIN."
 * }
 * 
 * What Happens:
 * ✓ Form submission stops
 * ✓ Error modal shows with PIN note
 * ✓ Wallet balance is NOT changed
 * ✓ No transaction recorded
 * ✓ User can try again
 */

// Trigger Example 1:
// window.handlePurchaseError({
//   "title": "Invalid PIN",
//   "message": "Invalid PIN. Please try again.",
//   "error_type": "validation",
//   "suggestion": "Please enter your 4-digit transaction PIN."
// });

/*
 * EXAMPLE 2: USER HAS INSUFFICIENT BALANCE
 * 
 * User Input:
 * - Network: Airtel
 * - Plan: 1GB Monthly (₦2,000)
 * - Phone: 08012345678
 * - PIN: 1234
 * - Wallet Balance: ₦500 (NOT ENOUGH!)
 * 
 * Server Response:
 * {
 *   "success": false,
 *   "title": "Insufficient Balance",
 *   "message": "Your wallet balance is not enough for this purchase.",
 *   "error_type": "insufficient_balance",
 *   "details": {
 *     "Required": "₦2,000.00",
 *     "Available Balance": "₦500.00",
 *     "Shortfall": "₦1,500.00"
 *   },
 *   "suggestion": "Please fund your wallet with at least ₦1,500.00 to complete this purchase."
 * }
 * 
 * What Happens:
 * ✓ Form submission stops
 * ✓ Error modal shows balance details
 * ✓ Wallet balance is NOT changed
 * ✓ No transaction recorded
 * ✓ User is told how much to add
 */

// Trigger Example 2:
// window.handlePurchaseError({
//   "title": "Insufficient Balance",
//   "message": "Your wallet balance is not enough for this purchase.",
//   "error_type": "insufficient_balance",
//   "details": {
//     "Required": "₦2,000.00",
//     "Available Balance": "₦500.00",
//     "Shortfall": "₦1,500.00"
//   },
//   "suggestion": "Please fund your wallet with at least ₦1,500.00 to complete this purchase."
// });

/*
 * EXAMPLE 3: API SERVICE IS DOWN
 * 
 * User Input: (All correct)
 * - Network: Glo
 * - Plan: 500MB Monthly (₦1,500)
 * - Phone: 08012345678
 * - PIN: 1234
 * - Wallet Balance: ₦5,000 (ENOUGH)
 * 
 * Problem: Data provider's API is temporarily down
 * 
 * Server Response:
 * {
 *   "success": false,
 *   "title": "Service Temporarily Unavailable",
 *   "message": "We're experiencing technical difficulties with the data service right now. 
               Your funds have not been deducted. Please try again in a few moments.",
 *   "error_type": "service_error",
 *   "details": {
 *     "Network": "Glo",
 *     "Package": "500MB - 1 Month",
 *     "Amount": "₦1,500.00"
 *   },
 *   "suggestion": "If the problem persists, please contact our support team. 
                  We apologize for the inconvenience."
 * }
 * 
 * What Happens:
 * ✓ Form submission stops (NO API call made)
 * ✓ Error modal shows service error
 * ✓ Wallet balance is NOT changed (database rollback)
 * ✓ Transaction is marked as "failed" for audit
 * ✓ User is told to try again later
 * ✓ Admin is notified via logs
 */

// Trigger Example 3:
// window.handlePurchaseError({
//   "title": "Service Temporarily Unavailable",
//   "message": "We're experiencing technical difficulties...",
//   "error_type": "service_error",
//   "details": {
//     "Network": "Glo",
//     "Package": "500MB - 1 Month",
//     "Amount": "₦1,500.00"
//   },
//   "suggestion": "If the problem persists, please contact our support team."
// });

/*
 * EXAMPLE 4: SUCCESSFUL PURCHASE
 * 
 * User Input: (All correct)
 * - Network: MTN
 * - Plan: 1GB Monthly (₦2,500)
 * - Phone: 08012345678
 * - PIN: 1234
 * - Wallet Balance: ₦5,000 (ENOUGH)
 * 
 * Server Response:
 * {
 *   "success": true,
 *   "message": "Your MTN data bundle (1GB - 1 Month) has been successfully purchased 
               and delivered to 08012345678!",
 *   "data": {
 *     "transaction_reference": "DATA_550e8400-e29b-41d4-a716-446655440000",
 *     "network": "MTN",
 *     "recipient": "08012345678",
 *     "package_name": "1GB - 1 Month",
 *     "amount": 2500,
 *     "formatted_amount": "₦2,500.00",
 *     "new_balance": 2500,
 *     "formatted_balance": "₦2,500.00",
 *     "transaction_time": "2026-05-01 14:30:00"
 *   }
 * }
 * 
 * What Happens:
 * ✓ Form submission succeeds
 * ✓ Success modal shows with transaction details
 * ✓ Wallet balance is DEDUCTED by ₦2,500
 * ✓ New balance shows: ₦2,500
 * ✓ Transaction is recorded as "success"
 * ✓ Data is delivered to recipient
 * ✓ Modal auto-closes after 8 seconds
 * ✓ User can make new purchase
 */

// Trigger Example 4:
// window.handlePurchaseSuccess({
//   "message": "Your MTN data bundle (1GB - 1 Month) has been successfully purchased...",
//   "data": {
//     "transaction_reference": "DATA_550e8400-e29b-41d4-a716-446655440000",
//     "network": "MTN",
//     "recipient": "08012345678",
//     "package_name": "1GB - 1 Month",
//     "amount": 2500,
//     "formatted_amount": "₦2,500.00",
//     "new_balance": 2500,
//     "formatted_balance": "₦2,500.00",
//     "transaction_time": "2026-05-01 14:30:00"
//   }
// });

// ============================================
// KEY SECURITY FEATURES
// ============================================

/*
 * 1. DATABASE TRANSACTIONS
 *    - BeginTransaction() starts atomic operation
 *    - All-or-nothing: Either everything succeeds or nothing does
 *    - If error occurs: RollBack() undoes all changes
 *    - Result: Wallet is NEVER partially updated
 * 
 * 2. WALLET LOCKING
 *    - lockForUpdate() prevents concurrent modifications
 *    - Ensures no race conditions
 *    - Only one process can modify wallet at a time
 * 
 * 3. ERROR HANDLING LAYERS
 *    - Input validation (local + server)
 *    - PIN verification
 *    - Wallet check
 *    - Product verification
 *    - API call
 *    - All with transaction rollback on any failure
 * 
 * 4. AUDIT TRAIL
 *    - Every transaction (success or failure) is logged
 *    - Transaction table records: reference, status, amount, response
 *    - Admin logs track all operations
 *    - Support can trace any issue
 * 
 * 5. USER COMMUNICATION
 *    - Clear, friendly error messages
 *    - No technical jargon
 *    - Suggestions on how to fix
 *    - Confirmation funds are safe
 */

// ============================================
// TESTING IN BROWSER CONSOLE
// ============================================

/*
 * TEST ERROR HANDLING:
 * 
 * 1. Invalid PIN Error:
 *    window.handlePurchaseError({
 *      title: 'Test Error',
 *      message: 'This is a test error',
 *      error_type: 'validation'
 *    })
 * 
 * 2. Success Modal:
 *    window.handlePurchaseSuccess({
 *      message: 'Test success',
 *      data: { 'Test Key': 'Test Value' }
 *    })
 * 
 * 3. Check wallet after error:
 *    - Manual check in wallet table should show NO deduction
 *    - Verify against "before" screenshot
 */

// ============================================
// PRODUCTION CHECKLIST
// ============================================

/*
 * Before going live:
 * 
 * ✓ Test all 4 examples above
 * ✓ Verify wallet balance unchanged on errors
 * ✓ Check transaction logs for failed attempts
 * ✓ Confirm success modal shows correct balance
 * ✓ Test with real payment data (small amounts)
 * ✓ Verify error emails are sent (if configured)
 * ✓ Check error logs: storage/logs/laravel.log
 * ✓ Load test with multiple concurrent purchases
 * ✓ Test with different networks: MTN, Glo, Airtel, 9Mobile
 * ✓ Verify messages are user-friendly
 * ✓ Test on mobile and desktop
 * ✓ Check accessibility (keyboard, screen readers)
 */
