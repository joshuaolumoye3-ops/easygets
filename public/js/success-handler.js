/**
 * Success Handler Utility for Data/Service Purchases
 * Displays formatted success messages with transaction details
 */

class PurchaseSuccessHandler {
    constructor() {
        this.modalId = 'successModalContainer';
        this.createModal();
    }

    /**
     * Create the success modal container if it doesn't exist
     */
    createModal() {
        if (document.getElementById(this.modalId)) return;

        const container = document.createElement('div');
        container.id = this.modalId;
        container.innerHTML = `
            <div id="successModalOverlay" class="fixed inset-0 bg-black/50 hidden z-50 transition-opacity duration-300"></div>
            <div id="successModalBox" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4 transition-all duration-300">
                <div id="successModalContent" class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95"></div>
            </div>
        `;
        document.body.appendChild(container);
    }

    /**
     * Show success modal with transaction data
     */
    show(successData) {
        const {
            message = 'Transaction completed successfully!',
            data = {}
        } = successData;

        const overlay = document.getElementById('successModalOverlay');
        const box = document.getElementById('successModalBox');
        const content = document.getElementById('successModalContent');

        // Build HTML
        const html = this.buildSuccessHTML(message, data);
        content.innerHTML = html;

        // Show modal with animation
        overlay.classList.remove('hidden');
        box.classList.remove('hidden');
        setTimeout(() => {
            overlay.classList.add('opacity-100');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);

        // Close button handler
        const closeBtn = content.querySelector('[data-close-success]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }

        // Close on overlay click
        overlay.addEventListener('click', () => this.hide());

        // Auto-hide after 8 seconds
        setTimeout(() => this.hide(), 8000);

        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                this.hide();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }

    /**
     * Build success HTML
     */
    buildSuccessHTML(message, data) {
        const detailsHTML = Object.entries(data).length > 0 ? `
            <div class="bg-white rounded-lg p-4 border border-green-100 shadow-sm">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Transaction Details
                </h4>
                <div class="space-y-2">
                    ${Object.entries(data).map(([key, value], index, array) => {
                        // Skip technical fields
                        if (['transaction_id', 'network_code'].includes(key)) return '';
                        
                        return `
                            <div class="flex justify-between items-center py-1">
                                <span class="text-sm text-gray-600">${key}:</span>
                                <span class="text-sm font-semibold text-gray-800 text-right ml-2">${this.escapeHtml(String(value))}</span>
                            </div>
                            ${value !== array[array.length - 1] ? '<hr class="border-gray-100">' : ''}
                        `;
                    }).join('')}
                </div>
            </div>
        ` : '';

        return `
            <div class="flex flex-col gap-4 p-6 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-2xl">
                <!-- Header -->
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center animate-pulse">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-green-800">Success! ✓</h3>
                        <p class="mt-1 text-sm text-green-700 leading-relaxed">${this.escapeHtml(message)}</p>
                    </div>
                    <button data-close-success class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Details -->
                ${detailsHTML}

                <!-- Security Message -->
                <div class="flex items-center justify-center pt-2">
                    <div class="flex items-center text-xs text-green-600">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Transaction completed securely
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 mt-2">
                    <button data-close-success class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                        Done
                    </button>
                    <button onclick="window.location.href='${window.location.pathname}'" class="flex-1 bg-green-100 hover:bg-green-200 text-green-700 font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                        New Purchase
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Hide success modal and redirect
     */
    hide(redirectUrl = null) {
        const overlay = document.getElementById('successModalOverlay');
        const box = document.getElementById('successModalBox');
        const content = document.getElementById('successModalContent');

        overlay.classList.remove('opacity-100');
        content.classList.add('scale-95');
        content.classList.remove('scale-100');

        setTimeout(() => {
            overlay.classList.add('hidden');
            box.classList.add('hidden');
            
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        }, 300);
    }

    /**
     * Safely escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize handler globally
const successHandler = new PurchaseSuccessHandler();

// Export for use in form submission handlers
window.handlePurchaseSuccess = (successData) => {
    successHandler.show(successData);
};
