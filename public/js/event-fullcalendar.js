const domevent = function(eventName, detail) {
    if (typeof Window.CustomEvent === "function") {
        return new CustomEvent(eventName, { bubbles: true, cancelable: true, detail });
    } else {
        const event = document.createEvent('CustomEvent');
        event.initCustomEvent(eventName, true, true, detail);
        return event;
    }
};


document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('civicrm-event-fullcalendar');

    let calendarParams = ({
        initialView: 'dayGridMonth',
        events: {
            url: '/civicrm/event/ical?reset=1&list=1',
            format: 'ics',
        },
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            omitZeroMinute: false,
            meridiem: 'short'
        },
        headerToolbar: {
            start: 'title',
            center: '',
            end: 'dayGridMonth,listMonth,timeGridWeek,timeGridDay today prev,next'
        }
    });

    calendarEl.dispatchEvent(domevent('fullcalendar:buildparams', calendarParams));

    const calendar = new FullCalendar.Calendar(calendarEl, calendarParams);

    calendarEl.dispatchEvent(domevent('fullcalendar:prerender'));
    calendar.render();
});
