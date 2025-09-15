const dropdowns = [
    { trigger: 'dropdownDefaultButton', target: 'dropdown' },
    { trigger: 'dropdownOutlinedButton', target: 'dropdownOutlined' },
    { trigger: 'dropdownIconButton', target: 'dropdownIcon' },
    { trigger: 'dropdownRadioBgHoverButton', target: 'dropdownRadioBgHover' },
    { trigger: 'dropdownToggleButton', target: 'dropdownToggle' },
    { trigger: 'dropdownCheckboxButton', target: 'dropdownDefaultCheckbox' },
    { trigger: 'dropdownSmallButton', target: 'dropdownSmall' },
    { trigger: 'dropdownLargeButton', target: 'dropdownLarge' },
    { trigger: 'dropdownLeftEndButton', target: 'dropdownLeftEnd' },
    { trigger: 'dropdownRightEndButton', target: 'dropdownRightEnd' },
    { trigger: 'dropdownTopButton', target: 'dropdownTop' },
    { trigger: 'dropdownBottomButton', target: 'dropdownBottom' },
    { trigger: 'dropdownRightButton', target: 'dropdownRight' },
    { trigger: 'dropdownLeftButton', target: 'dropdownLeft' }
];

function toggleDropdown(triggerElement, targetElement) {
    targetElement.classList.toggle('hidden');
}

function setupDropdownListeners() {
    dropdowns.forEach(dropdown => {
        const triggerEl = document.getElementById(dropdown.trigger);
        const targetEl = document.getElementById(dropdown.target);

        if (triggerEl && targetEl) {
            triggerEl.addEventListener('click', () => toggleDropdown(triggerEl, targetEl));
        }
    });

    document.addEventListener('click', function (event) {
        dropdowns.forEach(dropdown => {
            const triggerEl = document.getElementById(dropdown.trigger);
            const targetEl = document.getElementById(dropdown.target);

            if (targetEl && triggerEl) {
                if (!targetEl.contains(event.target) && !triggerEl.contains(event.target)) {
                    targetEl.classList.add('hidden');
                }
            }
        });
    });
}

setupDropdownListeners();