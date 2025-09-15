document.addEventListener('DOMContentLoaded', function () {
    const accordionElement = document.getElementById('accordion-collapse');

    // Create an array of accordion items based on the actual HTML structure
    const accordionItems = [
        {
            id: 'accordion-collapse-heading-1',
            triggerEl: document.querySelector('#accordion-collapse-heading-1 button'),
            targetEl: document.querySelector('#accordion-collapse-body-1'),
            active: true
        },
        {
            id: 'accordion-collapse-heading-2',
            triggerEl: document.querySelector('#accordion-collapse-heading-2 button'),
            targetEl: document.querySelector('#accordion-collapse-body-2'),
            active: false
        },
        {
            id: 'accordion-collapse-heading-3',
            triggerEl: document.querySelector('#accordion-collapse-heading-3 button'),
            targetEl: document.querySelector('#accordion-collapse-body-3'),
            active: false
        },
        {
            id: 'accordion-collapse-heading-4',
            triggerEl: document.querySelector('#accordion-collapse-heading-4 button'),
            targetEl: document.querySelector('#accordion-collapse-body-4'),
            active: true
        },
        {
            id: 'accordion-collapse-heading-5',
            triggerEl: document.querySelector('#accordion-collapse-heading-5 button'),
            targetEl: document.querySelector('#accordion-collapse-body-5'),
            active: false
        },
        {
            id: 'accordion-collapse-heading-6',
            triggerEl: document.querySelector('#accordion-collapse-heading-6 button'),
            targetEl: document.querySelector('#accordion-collapse-body-6'),
            active: false
        },
        {
            id: 'accordion-collapse-heading-7',
            triggerEl: document.querySelector('#accordion-collapse-heading-7 button'),
            targetEl: document.querySelector('#accordion-collapse-body-7'),
            active: true
        },
        {
            id: 'accordion-collapse-heading-8',
            triggerEl: document.querySelector('#accordion-collapse-heading-8 button'),
            targetEl: document.querySelector('#accordion-collapse-body-8'),
            active: false
        },
        {
            id: 'accordion-collapse-heading-9',
            triggerEl: document.querySelector('#accordion-collapse-heading-9 button'),
            targetEl: document.querySelector('#accordion-collapse-body-9'),
            active: false
        },
        {
            id: 'accordion-collapse-heading-10',
            triggerEl: document.querySelector('#accordion-collapse-heading-10 button'),
            targetEl: document.querySelector('#accordion-collapse-body-10'),
            active: true
        },
        {
            id: 'accordion-collapse-heading-11',
            triggerEl: document.querySelector('#accordion-collapse-heading-11 button'),
            targetEl: document.querySelector('#accordion-collapse-body-11'),
            active: false
        },
        {
            id: 'accordion-collapse-heading-12',
            triggerEl: document.querySelector('#accordion-collapse-heading-12 button'),
            targetEl: document.querySelector('#accordion-collapse-body-12'),
            active: false
        },
    ];

    // Options with default values
    const options = {
        alwaysOpen: false,
        activeClasses: 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white',
        inactiveClasses: 'text-gray-500 dark:text-gray-400',
        onOpen: (item) => {
            // Smooth transition with max-height animation
            const icon = item.triggerEl.querySelector('[data-accordion-icon]');
            const content = item.targetEl;

            // Remove hidden class and prepare for animation
            content.classList.remove('hidden');
            content.style.maxHeight = '0px';
            content.style.overflow = 'hidden';
            content.style.transition = 'max-height 0.3s ease-out';

            // Trigger reflow to ensure transition works
            content.offsetHeight;

            // Animate to full height
            content.style.maxHeight = `${content.scrollHeight}px`;

            // Rotate icon
            if (icon) {
                icon.classList.remove('rotate-180');
            }

            // Update aria attributes
            item.triggerEl.setAttribute('aria-expanded', 'true');
        },
        onClose: (item) => {
            const icon = item.triggerEl.querySelector('[data-accordion-icon]');
            const content = item.targetEl;

            // Animate height to 0
            content.style.maxHeight = '0px';

            // After animation, add hidden class
            setTimeout(() => {
                content.classList.add('hidden');
                content.style.maxHeight = '';
                content.style.overflow = '';
                content.style.transition = '';
            }, 300);

            // Rotate icon
            if (icon) {
                icon.classList.add('rotate-180');
            }

            // Update aria attributes
            item.triggerEl.setAttribute('aria-expanded', 'false');
        },
        onToggle: (item) => {
            // Close other accordion items if alwaysOpen is false
            if (!options.alwaysOpen) {
                accordionItems.forEach(otherItem => {
                    if (otherItem !== item && !otherItem.targetEl.classList.contains('hidden')) {
                        options.onClose(otherItem);
                    }
                });
            }
        }
    };

    // Add click event listeners to trigger elements
    accordionItems.forEach(item => {
        if (item.triggerEl) {
            item.triggerEl.addEventListener('click', () => {
                const isCurrentlyHidden = item.targetEl.classList.contains('hidden');

                // If the item is closed, open it
                if (isCurrentlyHidden) {
                    options.onOpen(item);
                }
                // If the item is open, close it (only if alwaysOpen is false)
                else if (!options.alwaysOpen) {
                    options.onClose(item);
                }

                // Call onToggle for any additional logic
                options.onToggle(item);
            });
        }
    });

    // Initial state setup
    accordionItems.forEach(item => {
        if (!item.active) {
            options.onClose(item);
        } else {
            options.onOpen(item);
        }
    });
});