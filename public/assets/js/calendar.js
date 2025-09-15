document.addEventListener('DOMContentLoaded', function () {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    initialView: 'dayGridMonth',
    selectable: true, // Enable selecting date ranges
    editable: true,   // Enable drag-and-drop of events
    events: [
      {
        title: 'All Day Event',
        start: '2025-01-08'
      },
      {
        title: 'Conference',
        start: '2025-01-15',
        end: '2025-01-17',
        color: '#57a3f3'
      },
      {
        title: 'Long Event',
        start: '2025-01-01',
        end: '2025-01-07'
      },
      {
        id: 999,
        title: 'Repeating Event',
        start: '2025-01-09T16:00:00'
      },
      {
        id: 999,
        title: 'Repeating Event',
        start: '2025-01-16T16:00:00'
      },


    ],
    dateClick: function (info) {
      // Action when a date is clicked
      alert('Clicked on: ' + info.dateStr);
    },
    eventClick: function (info) {
      // Action when an event is clicked
      alert('Event: ' + info.event.title);
    },
    select: function (info) {
      // Action when a range is selected
      var title = prompt('Enter event title:');
      if (title) {
        calendar.addEvent({
          title: title,
          start: info.startStr,
          end: info.endStr,
          allDay: info.allDay
        });
        alert('Event added!');
      }
      calendar.unselect(); // Deselect the range
    },
    eventDrop: function (info) {
      // Action after an event is dragged and dropped
      alert('Event moved to: ' + info.event.start.toISOString());
    }
  });
  calendar.render();
});