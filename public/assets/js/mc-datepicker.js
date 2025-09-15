/* javascript */
// const datePicker = MCDatepicker.create({
//     el: '#mc-datepicker',
//     bodyType: 'modal'
// });

// Initialize Flatpickr
document.addEventListener("DOMContentLoaded", function () {
    flatpickr("#datepicker", {
        enableTime: false, // Disable time selection
        dateFormat: "Y-m-d", // Format: YYYY-MM-DD
        // minDate: "today", // Disable past dates
        // maxDate: "2025-12-31", // Set max selectable date
    });

    // Initialize Time Picker
    flatpickr("#timepicker", {
        enableTime: true, // Time selection enabled
        noCalendar: true, // Disable the calendar
        dateFormat: "h:i K", // Format: HH:MM (24-hour format)
        time_24hr: false, // Use 24-hour format
        defaultHour: 12, // Default hour (12 PM)
        defaultMinute: 30, // Default minute (30)
    });
});