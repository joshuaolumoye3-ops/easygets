// Select all modals and toggle/hide elements
const modals = document.querySelectorAll('[id$="-modal"]');
const toggleButtons = document.querySelectorAll('[data-modal-toggle]');
const hideButtons = document.querySelectorAll('[data-modal-hide]');

// Options with default values
const options = {
    placement: 'bottom-right', // Placement can be used if dynamic positioning is added
    backdrop: 'dynamic',
    backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
    closable: true,
    onHide: (modalId) => {
        // console.log(`Modal with ID ${modalId} is hidden`);
    },
    onShow: (modalId) => {
        // console.log(`Modal with ID ${modalId} is shown`);
    },
    onToggle: (modalId) => {
        // console.log(`Modal with ID ${modalId} has been toggled`);
    },
};

// Helper functions
function showModal(modalEl) {
    if (modalEl) {
        modalEl.classList.remove('hidden');
        modalEl.setAttribute('aria-hidden', 'false');
        modalEl.classList.add('flex');
        options.onShow(modalEl.id);
    }
}

function hideModal(modalEl) {
    if (modalEl) {
        modalEl.classList.add('hidden');
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.classList.remove('flex');
        options.onHide(modalEl.id);
    }
}

function toggleModal(modalEl) {
    if (modalEl.classList.contains('hidden')) {
        showModal(modalEl);
    } else {
        hideModal(modalEl);
    }
    options.onToggle(modalEl.id);
}

// Event listeners for toggle buttons
toggleButtons.forEach((button) => {
    const modalId = button.getAttribute('data-modal-toggle');
    const modalEl = document.getElementById(modalId);
    button.addEventListener('click', () => toggleModal(modalEl));
});

// Event listeners for hide buttons
hideButtons.forEach((button) => {
    const modalId = button.getAttribute('data-modal-hide');
    const modalEl = document.getElementById(modalId);
    button.addEventListener('click', () => hideModal(modalEl));
});

// Close modal on backdrop click if closable is true
modals.forEach((modalEl) => {
    if (options.closable) {
        modalEl.addEventListener('click', (event) => {
            if (event.target === modalEl) {
                hideModal(modalEl);
            }
        });
    }
});
