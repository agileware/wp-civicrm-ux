const domevent = function (eventName, detail) {
    if (typeof Window.CustomEvent === "function") {
        return new CustomEvent(eventName, {
            bubbles: true,
            cancelable: true,
            detail,
        });
    } else {
        const event = document.createEvent("CustomEvent");
        event.initCustomEvent(eventName, true, true, detail);
        return event;
    }
};

/*
  Convert ISO8601 date object to AM/PM time format
  E.g. 2021-02-02T09:30:00+0000 => 9:30AM
*/
const formatAMPM = function (date) {
    return date.toLocaleTimeString([], {timeStyle: "short"});
}

const hidePopup = function () {
    document.querySelector(".civicrm-ux-event-popup").style.display = "none";
    document.getElementById("civicrm-event-fullcalendar").style.display = "flex";
}

const sameDay = function (d1, d2) {
    return d1.getFullYear() === d2.getFullYear() &&
        d1.getMonth() === d2.getMonth() &&
        d1.getDate() === d2.getDate();
}

let events_loaded = false;
let prev_rendered_date;
let prev_rendered_date_visible = false;

// Colour scheme for different event type labels
const colors = uxFullcalendar.colors || {};
const event_types = uxFullcalendar.filterTypes || [];

const events = function (info, successCallback, failureCallback) {
    if (!events_loaded) {
        // Make AJAX request (themes > functions.php) to get all events from 1 year ago until now
        jQuery.ajax({
            method: "GET",
            dataType: "json",
            url: uxFullcalendar.ajax_url + 'civicrm_ux/get_events_all',
            data: {
                type: uxFullcalendar.types,
                upload: uxFullcalendar.upload,
                colors: colors,
                start_date: uxFullcalendar.start,
                image_id_field: uxFullcalendar.image_id_field,
                image_src_field: uxFullcalendar.image_src_field,
                force_login: uxFullcalendar.force_login,
                redirect_after_login: uxFullcalendar.redirect_after_login,
                extra_fields: uxFullcalendar.extra_fields
            },
            // Store events in client's browser after success to prevent further AJAX requests
            success: function (response) {
                all_the_events = response.result;
                events_loaded = true;
                successCallback(response.result);
            },
        });
    } else {
        successCallback(all_the_events);
    }
};

const eventContent = function (arg) {
    const selector_val = document.querySelector("#event-selector")?.value ?? 'all';

    const {
        extendedProps: { html_entry, event_type }
    } = arg.event;

    if (
        selector_val == "all" ||
        selector_val == arg.event.extendedProps.event_type
    ) {
        const eventTemplate = document.createElement("template");
        eventTemplate.innerHTML = html_entry;

        const eventHolder = eventTemplate.content.firstElementChild;
        eventHolder.classList.add("event-holder");

        if (colors.hasOwnProperty(event_type))
            eventHolder.style.backgroundColor = colors[event_type];

        return { domNodes: eventTemplate.content.childNodes };
    }

    return {};
}

const eventDidMount = function (info) {
    let selector_val = document.querySelector("#event-selector")?.value ?? 'all';

    const event_start = new Date(info.event.start);
    const event_end = new Date(info.event.end);

    if (info.view.type == "listMonth") {
        if (prev_rendered_date != event_start.getDate()) {
            prev_rendered_date = event_start.getDate;
            prev_rendered_date_visible = false;
        }
        const onthisdate =
            event_start.getFullYear() +
            "-" +
            (event_start.getMonth() + 1 + '').padStart(2, '0')
            "-" +
            (event_start.getDate() + '').padStart(2, '0');

        let date_row = document.querySelector("[data-date='" + onthisdate + "']");

        if(date_row) {
            if (!prev_rendered_date_visible) {
                date_row.style.display = "none";
            } else {
                date_row.style.display = "table-row";
            }

            if (info.event.extendedProps.event_type == selector_val) {
                prev_rendered_date_visible = true;
                date_row.style.display = "table-row";
            }

            if (selector_val == "all") {
                date_row.style.display = "table-row";
            }
        }

        prev_rendered_date = event_start.getDate();

        if (
            info.event.extendedProps.event_type != selector_val &&
            !prev_rendered_date_visible
        ) {
            prev_rendered_date_visible = false;
        }
    }

    if (
        selector_val == "all" ||
        selector_val == info.event.extendedProps.event_type
    ) {
        let event_img = info.event.extendedProps["file.uri"] ? info.event.extendedProps["image_url"] : "";


        if (info.view.type == "listMonth") {
            const template = info.event.extendedProps.html_render;
            info.el.innerHTML = template;
        } else {
            const event_location = info.event.extendedProps.country;

            const eventElements = {
                eventImg: '',
                eventLocation: '',
                eventTimeText: `<span class="event-time-text">${formatAMPM(event_start)} to ${formatAMPM(event_end)}</span>`,
            }

            if(info.event.extendedProps["file.uri"]) {
                eventElements.eventImg = `<img id="event-img" src="${event_img}">`;
            }

            if(event_location) {
                eventElements.eventLocation = `<div class="event-location"><i class="fa fa-map-marker"></i><span class="event-location-text">${event_location}</span></div>`
            }

            info.view.calendar.el.dispatchEvent(domevent('fullcalendar:buildTippy', eventElements));

            const template = document.createElement('template');

            template.innerHTML =
                `<div class="event-name">${info.event.title}</div>
                 ${eventElements.eventImg}
                 <div class="event-time"><i class="fa fa-clock-o"></i>${eventElements.eventTimeText}</div>
                 ${eventElements.eventLocation}`;

            if(colors.hasOwnProperty(info.event.extendedProps.event_type)) {
                template.content.querySelector('.event-name').style.backgroundColor = colors[info.event.extendedProps.event_type];
            }

            info.view.calendar.el.dispatchEvent(domevent('fullcalendar:alterTippy', template.content));

            let tooltip = new tippy(info.el, {
                interactive: true,
                delay: 300,
                maxWidth: 400,
                allowHTML: true,
                placement: "top",
                content: template.content,
            });
        }
    } else {
        info.el.innerHTML = "";
    }
};

const eventClick = function ({el, event, jsEvent, view}) {
    jsEvent.preventDefault();

    let event_container = document.querySelector(
        ".civicrm-ux-event-popup-container"
    );
    let popup = document.querySelector(".civicrm-ux-event-popup");
    let popup_container = document.getElementById("civicrm-ux-event-popup-content");
    event_container.style.display = "none";
    view.calendar.el.style.display = "none";
    popup.style.display = "block";
    popup_container.innerHTML = event.extendedProps.html_render;

    event_container.style.display = "block";
}

document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.getElementById("civicrm-event-fullcalendar");

    /* 
      This object defines the custom parameters for FullCalendar's library incl. buttons, logic, views
    */
    let calendarParams = {
        initialView: "dayGridMonth",
        nextDayThreshold: '09:00:00',
        events,
        eventTimeFormat: {
            hour: "numeric",
            minute: "2-digit",
            omitZeroMinute: false,
            meridiem: "short",
        },
        customButtons: {
            sortByType: {
                text: "Filter by Event Type",
            },
        },
        headerToolbar: {
            left: "dayGridMonth,listMonth sortByType",
            center: "",
            end: "prevYear,prev title next,nextYear",
        },
        buttonText: {
            dayGridMonth: "Calendar View",
            listMonth: "List View",
            prevYear: "« Previous year",
            prev: "‹ Previous month",
            nextYear: "Next year »",
            next: "Next month ›",
        },
        buttonIcons: {
            prevYear: "chevrons-left",
            prev: "chevron-left",
            nextYear: "chevrons-right",
            next: "chevron-right",
        },
        firstDay: 1,
        // Event render hooks (eventContent, eventDidMount, eventClick) define the custom logic for FCVIC's fullcalendar

        // Content injection to change the colour of event labels (by type)
        eventContent,

        // Executed for each event when they are added to the DOM
        eventDidMount,

        // Callback to be run when the calendar entry is clicked
        eventClick,

        ...(window.uxFullcalendar.calendar_params || {})
    };

    calendarEl.dispatchEvent(
        domevent("fullcalendar:buildparams", calendarParams)
    );

    const calendar = new FullCalendar.Calendar(calendarEl, calendarParams);

    calendarEl.dispatchEvent(domevent("fullcalendar:prerender"));
    calendar.render();

    const sortByTypeBtn = document.querySelector(".fc-sortByType-button");

    if (!sortByTypeBtn)
        return;

    const sortByTypeSelect = document.createElement("select");
    sortByTypeSelect.classList.add("fc-button");
    sortByTypeSelect.classList.add("fc-button-primary");
    sortByTypeSelect.innerHTML = '<option selected value="all">Filter by Event Type</option>';
    for (const type of event_types) {
        sortByTypeSelect.innerHTML += '<option value="' + type + '">' + type + '</option>';
    }
    sortByTypeSelect.setAttribute("id", "event-selector");
    sortByTypeBtn.style.display = "none";
    sortByTypeBtn.after(sortByTypeSelect);

    sortByTypeSelect.addEventListener("change", () => calendar.refetchEvents());
});
  