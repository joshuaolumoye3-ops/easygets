/**
 * Error Handler Utility for Data/Service Purchases
 * Displays user-friendly error messages in a modal format
 */

class PurchaseErrorHandler {
    constructor() {
        this.modalId = 'errorModalContainer';
        this.createModal();
    }

    /**
     * Create the error modal container if it doesn't exist
     */
    createModal() {
        if (document.getElementById(this.modalId)) return;

        const container = document.createElement('div');
        container.id = this.modalId;
        container.innerHTML = `
            <div id="errorModalOverlay" class="fixed inset-0 bg-black/50 hidden z-50 transition-opacity duration-300"></div>
            <div id="errorModalBox" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4 transition-all duration-300">
                <div id="errorModalContent" class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95"></div>
            </div>
        `;
        document.body.appendChild(container);
    }

    /**
     * Show error modal with structured error data
     */
    show(errorData) {
        const {
            title = 'Error',
            message = 'Something went wrong.',
            error_type = 'error',
            details = {},
            suggestion = null
        } = errorData;

        const overlay = document.getElementById('errorModalOverlay');
        const box = document.getElementById('errorModalBox');
        const content = document.getElementById('errorModalContent');

        // Build HTML
        const html = this.buildErrorHTML(title, message, error_type, details, suggestion);
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
        const closeBtn = content.querySelector('[data-close-error]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }

        // Close on overlay click
        overlay.addEventListener('click', () => this.hide());

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
     * Build error HTML based on error type
     */
    buildErrorHTML(title, message, errorType, details, suggestion) {
        const isWarning = errorType === 'warning';
        const iconColor = isWarning ? 'text-amber-600' : 'text-red-600';
        const bgColor = isWarning ? 'from-amber-50 to-orange-50' : 'from-red-50 to-rose-50';
        const borderColor = isWarning ? 'border-amber-200' : 'border-red-200';
        const titleColor = isWarning ? 'text-amber-800' : 'text-red-800';
        const msgColor = isWarning ? 'text-amber-700' : 'text-red-700';
        const dotBgColor = isWarning ? 'bg-amber-100' : 'bg-red-100';

        const detailsHTML = Object.entries(details).length > 0 ? `
            <div class="bg-white rounded-lg p-4 border ${borderColor === 'border-red-200' ? 'border-red-100' : 'border-amber-100'} shadow-sm">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Details
                </h4>
                <div class="space-y-2">
                    ${Object.entries(details).map(([key, value], index, array) => `
                        <div class="flex justify-between items-center py-1">
                            <span class="text-sm text-gray-600">${key}:</span>
                            <span class="text-sm font-semibold text-gray-800 text-right ml-2">${value}</span>
                        </div>
                        ${index < array.length - 1 ? '<hr class="border-gray-100">' : ''}
                    `).join('')}
                </div>
            </div>
        ` : '';

        const suggestionHTML = suggestion ? `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs text-blue-700">${suggestion}</p>
                </div>
            </div>
        ` : '';

        return `
            <div class="flex flex-col gap-4 p-6 bg-gradient-to-br ${bgColor} border ${borderColor} rounded-2xl">
                <!-- Header -->
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 ${dotBgColor} rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 ${iconColor}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4v2M9 3h6a2 2 0 012 2v18a2 2 0 01-2 2H9a2 2 0 01-2-2V5a2 2 0 012-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold ${titleColor}">${this.escapeHtml(title)}</h3>
                        <p class="mt-1 text-sm ${msgColor} leading-relaxed">${this.escapeHtml(message)}</p>
                    </div>
                    <button data-close-error class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Details -->
                ${detailsHTML}

                <!-- Suggestion -->
                ${suggestionHTML}

                <!-- Safety Message -->
                <div class="flex items-center justify-center pt-2">
                    <div class="flex items-center text-xs ${isWarning ? 'text-amber-600' : 'text-red-600'}">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Your wallet balance was not affected by this transaction
                    </div>
                </div>

                <!-- Action Button -->
                <button data-close-error class="w-full mt-2 bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                    Understood
                </button>
            </div>
        `;
    }

    /**
     * Hide error modal
     */
    hide() {
        const overlay = document.getElementById('errorModalOverlay');
        const box = document.getElementById('errorModalBox');
        const content = document.getElementById('errorModalContent');

        overlay.classList.remove('opacity-100');
        content.classList.add('scale-95');
        content.classList.remove('scale-100');

        setTimeout(() => {
            overlay.classList.add('hidden');
            box.classList.add('hidden');
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
const errorHandler = new PurchaseErrorHandler();

// Export for use in form submission handlers
window.handlePurchaseError = (errorData) => {
    errorHandler.show(errorData);
};

// Usage in form submission:
// document.getElementById('purchaseForm').addEventListener('submit', async (e) => {
//     e.preventDefault();
//     const response = await fetch(form.action, {
//         method: 'POST',
//         body: new FormData(form)
//     });
//     const data = await response.json();
//     
//     if (!data.success) {
//         window.handlePurchaseError(data);
//     } else {
//         // Show success
//     }
// });
