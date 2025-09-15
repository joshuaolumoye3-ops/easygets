// Tooltips setup
document.querySelectorAll('[data-tooltip-target]').forEach(trigger => {
    const tooltipId = trigger.getAttribute('data-tooltip-target');
    const tooltipElement = document.getElementById(tooltipId);

    if (!tooltipElement) return;

    // Show tooltip on hover
    trigger.addEventListener('mouseenter', () => {
        tooltipElement.classList.remove('invisible', 'opacity-0');
        tooltipElement.classList.add('visible', 'opacity-100');
    });

    // Hide tooltip when hover ends
    trigger.addEventListener('mouseleave', () => {
        tooltipElement.classList.remove('visible', 'opacity-100');
        tooltipElement.classList.add('invisible', 'opacity-0');
    });
});


// Popover setup
document.querySelectorAll('[data-popover-target]').forEach(trigger => {
    const popoverId = trigger.getAttribute('data-popover-target');
    const popoverElement = document.getElementById(popoverId);

    if (!popoverElement) return;

    // Show tooltip on hover
    trigger.addEventListener('mouseenter', () => {
        popoverElement.classList.remove('invisible', 'opacity-0');
        popoverElement.classList.add('visible', 'opacity-100');
    });

    // Hide tooltip when hover ends
    trigger.addEventListener('mouseleave', () => {
        popoverElement.classList.remove('visible', 'opacity-100');
        popoverElement.classList.add('invisible', 'opacity-0');
    });
});
