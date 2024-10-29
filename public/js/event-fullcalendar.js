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
  const formatAMPM = function(date) {
    return date.toLocaleTimeString([], { timeStyle: "short" });
  }

  const hidePopup = function() {
    document.querySelector(".civicrm-ux-event-popup").style.display = "none";
    document.getElementById("civicrm-event-fullcalendar").style.display = "flex";
  }

  const sameDay = function(d1, d2) {
    return d1.getFullYear() === d2.getFullYear() &&
      d1.getMonth() === d2.getMonth() &&
      d1.getDate() === d2.getDate();
  }
  
  let events_loaded = false;
  let prev_rendered_date;
  let prev_rendered_date_visible = false;
  
  document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.getElementById("civicrm-event-fullcalendar");
  
    // Colour scheme for different event type labels
    const colors = uxFullcalendar.colors || {};
    const event_types = uxFullcalendar.types?.split(',') || [];

    /* 
      This object defines the custom parameters for FullCalendar's library incl. buttons, logic, views
    */
    let calendarParams = {
      initialView: "dayGridMonth",
      nextDayThreshold: '09:00:00',
      events: function (info, successCallback, failureCallback) {
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
      },
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
      eventContent: function (arg) {
        const selector_val = document.querySelector("#event-selector").value;
  
        if (
          selector_val == "all" ||
          selector_val == arg.event.extendedProps.event_type
        ) {
          const eventTemplate = document.createElement("template");
          eventTemplate.innerHTML = arg.event.extendedProps.html_entry;

          const eventHolder = eventTemplate.content.firstElementChild;
          eventHolder.classList.add("event-holder");

          if (colors.hasOwnProperty(arg.event.extendedProps.event_type))
            eventHolder.style.backgroundColor = colors[arg.event.extendedProps.event_type];

          return { domNodes: Array.from(eventTemplate.content.childNodes) };
        }

        return {};
      },
  
      // Executed for each event when they are added to the DOM
      eventDidMount: function (info) {
        let selector_val = document.querySelector("#event-selector").value;
  
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
            (event_start.getMonth() + 1 < 10
              ? "0" + (event_start.getMonth() + 1)
              : event_start.getMonth() + 1) +
            "-" +
            (event_start.getDate() < 10
              ? "0" + event_start.getDate()
              : event_start.getDate());
  
          let date_row = document.querySelectorAll(
            "[data-date='" + onthisdate + "']"
          )[0];
  
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

            const template = `
                      <div style="background-color: ${
                        Object.keys(colors).length > 0 ? '#' + colors[info.event.extendedProps.event_type] : '#333333'
                      }" class="event-name">${info.event.title}</div>
                      ${
                        info.event.extendedProps["file.uri"]
                          ? '<img id="event-img" src="' + event_img + '">'
                          : ""
                      }
                      <div class="event-time"><i class="fa fa-clock-o"></i><span class="event-time-text">${
                        formatAMPM(event_start) + " to " + formatAMPM(event_end)
                      }</span></div>
                      ${
                        event_location
                          ? '<div class="event-location"><i class="fa fa-map-marker"></i><span class="event-location-text">' + event_location + '</span></div>'
                          : ""
                      }
                      `;
  
            let tooltip = new tippy(info.el, {
              interactive: true,
              delay: 300,
              maxWidth: 400,
              allowHTML: true,
              placement: "top",
              content(reference) {
                return template;
              },
            });
          }
        } else {
          info.el.innerHTML = "";
        }
      },
  
      eventClick: function (eventClickInfo) {
        let jsEvent = eventClickInfo.jsEvent;
        jsEvent.preventDefault();
  
        let event_container = document.querySelector(
          ".civicrm-ux-event-popup-container"
        );
        let popup = document.querySelector(".civicrm-ux-event-popup");
        let popup_container = document.getElementById("civicrm-ux-event-popup-content");
        event_container.style.display = "none";
        calendarEl.style.display = "none";
        popup.style.display = "block";
        popup_container.innerHTML = eventClickInfo.event.extendedProps.html_render;
  
        event_container.style.display = "block";
      },
    };
  
    calendarEl.dispatchEvent(
      domevent("fullcalendar:buildparams", calendarParams)
    );
  
    const calendar = new FullCalendar.Calendar(calendarEl, calendarParams);
  
    calendarEl.dispatchEvent(domevent("fullcalendar:prerender"));
    calendar.render();

    const sortByTypeBtn = document.querySelector(".fc-sortByType-button");
    const sortByTypeSelect = document.createElement("select");
    sortByTypeSelect.classList.add("fc-button");
    sortByTypeSelect.classList.add("fc-button-primary");
    sortByTypeSelect.innerHTML = '<option selected value="all">Filter by Event Type</option>';
    for (const i in event_types) {
      sortByTypeSelect.innerHTML += '<option value="' + event_types[i] + '">' + event_types[i] + '</option>';
    }
    sortByTypeSelect.setAttribute("id", "event-selector");
    sortByTypeBtn.style.display = "none";
    sortByTypeBtn.after(sortByTypeSelect);
  
    sortByTypeSelect.addEventListener("change", () => calendar.refetchEvents());
  });
  