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
  function formatAMPM(date) {
    let hours = date.getHours();
    let minutes = date.getMinutes();
    const ampm = hours >= 12 ? "am" : "pm";
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? "0" + minutes : minutes;
    const strTime = hours + ":" + minutes + " " + ampm;
    return strTime;
  }
  
  // Used to convert an index to a day-of-the-week/month
  // E.g. days[Date.getDay()] => days[1] => Monday 
  const days = [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ];
  const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  
  /*
    Convert ISO8601 date object to human readable format
    E.g. 2021-02-02T09:30:00+0000 => Tuesday, 2 February 2021
  */
  function formatDay(date) {
    return (
      days[date.getDay()] +
      ", " +
      date.getDate() +
      " " +
      months[date.getMonth()] +
      " " +
      date.getFullYear()
    );
  }
  
  let events_loaded = false;
  let prev_rendered_date;
  let prev_rendered_date_visible = false;
  
  document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.getElementById("civicrm-event-fullcalendar");
  
    // ColoUr scheme for different event type labels
    const colors = {
      CPD: {
        color: "#52246d",
        textColor: "white",
      },
      "Board meeting": {
        color: "#c45472",
        textColor: "white",
      },
      "Agency Managers meeting": {
        color: "#ce8d8b",
        textColor: "white",
      },
      "Working Group": {
        color: "#ca78b1",
        textColor: "white",
      },
      Networks: {
        color: "#ac5653",
        textColor: "white",
      },
      Conference: {
        color: "#e27270",
        textColor: "white",
      },
      Meeting: {
        color: "#a04486",
        textColor: "white",
      },
    };
  
    /* 
      This object defines the custom parameters for FullCalendar's library incl. buttons, logic, views
    */
    let calendarParams = {
      initialView: "dayGridMonth",
      events: function (info, successCallback, failureCallback) {
        if (!events_loaded) {
          // Make AJAX request (themes > functions.php) to get all events from 1 year ago until now
          jQuery.ajax({
            method: "GET",
            dataType: "json",
            url: wp_site_obj.ajax_url,
            data: {
              action: "get_events_all",
              type: "CPD,Board meeting,Agency Managers meeting,Working Group,Networks,Conference,Meeting",
              start_date: new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().split('T')[0], // 1 year previously
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
          text: "Sort by type",
        },
      },
      headerToolbar: {
        left: "dayGridMonth,listMonth sortByType",
        center: "",
        end: "prevYear,prev title next,nextYear",
      },
      buttonText: {
        dayGridMonth: "Events",
        listMonth: "List",
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
        let selector_val = document.querySelector("#event-selector").value;
  
        if (
          selector_val == "all" ||
          selector_val == arg.event.extendedProps.event_type
        ) {
          let eventHolder = document.createElement("div");
          eventHolder.classList.add("event-holder");
          eventHolder.style.backgroundColor =
            colors[arg.event.extendedProps.event_type].color;
          eventHolder.innerHTML =
            '<div style="line-height: 1;" class="fc-event-title"><a style="font-size: 11px; font-weight: normal; text-decoration: none; color: ' +
            colors[arg.event.extendedProps.event_type].textColor +
            ';" href="' +
            arg.event.url +
            '">' +
            arg.event.title +
            "</a></div>";
  
          let arrayOfDomNodes = [eventHolder];
          return { domNodes: arrayOfDomNodes };
        } else return {};
      },
  
      // Executed for each event when they are added to the DOM
      eventDidMount: function (info) {
        let selector_val = document.querySelector("#event-selector").value;
  
        const event_start = new Date(info.event.start);
        const event_end = new Date(info.event.end);
        const day_start = formatDay(event_start);
  
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
          let event_img =
            wp_site_obj.upload.baseurl +
            "/" +
            info.event.extendedProps["file.uri"];
          let event_title = info.event.title;
          const event_id = info.event.id;
  
          let event_location = info.event.extendedProps.street_address
            ? info.event.extendedProps.street_address + ", "
            : "";
          event_location += info.event.extendedProps.country
            ? info.event.extendedProps.country
            : "";
  
          if (info.event.extendedProps.zoom == "1") {
            event_location = info.event.extendedProps.country
              ? "Online, " + info.event.extendedProps.country
              : "Online";
          }
  
          const event_time =
            "&nbsp;&nbsp;" +
            day_start +
            '</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-clock-o"></i>&nbsp;&nbsp;<span id="event-time-text">' +
            formatAMPM(event_start) +
            " to " +
            formatAMPM(event_end);
  
          if (info.view.type == "listMonth") {
            const template = `<div class="civicrm-ux-event-listing">
                          <div class="civicrm-ux-event-listing-image">${
                            info.event.extendedProps["file.uri"]
                              ? '<img src="' + event_img + '">'
                              : ""
                          }</div>
                          <div class="civicrm-ux-event-listing-type" style="background-color: ${
                            colors[info.event.extendedProps.event_type].color
                          };">${
              info.event.extendedProps.level
                ? info.event.extendedProps.level + " SESSION"
                : info.event.extendedProps.event_type
            }</div>
                          <div class="civicrm-ux-event-listing-name">${event_title}</div>
                          <div class="civicrm-ux-event-listing-date"><i class="fa fa-calendar-o"></i><span id="event-time-text">${event_time}</span></div>
                          <div class="civicrm-ux-event-listing-location"><i class="fa fa-map-marker-alt"></i>&nbsp;&nbsp;<span id="event-time-text">${event_location}</span></div>
                          <div class="civicrm-ux-event-listing-cpd-points">${
                            info.event.extendedProps.cpd_points > 0
                              ? '<i class="fa fa-check-square"></i>&nbsp;&nbsp;<span id="event-time-text">' +
                                info.event.extendedProps.cpd_points +
                                " CPD Points</span>"
                              : ""
                          }${
              info.event.extendedProps.category.length > 0
                ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-flag"></i>&nbsp;&nbsp;' +
                  info.event.extendedProps.category.join([separator = ', '])
                : ""
            }</div>
                          <div class="civicrm-ux-event-listing-register" onclick="window.location.href='${
                            info.event.url
                          }';">Click here to register</div>
                          <div class="civicrm-ux-event-listing-desc">${
                            info.event._def.extendedProps.description
                              ? info.event._def.extendedProps.description
                              : "No event description provided"
                          }</div>
                          <hr>
                      </div>`;
            info.el.innerHTML = template;
          } else {
            event_title = info.event.title;
            event_location = info.event.extendedProps.country;
  
            const template = `
                      <div style="background-color: ${
                        colors[info.event.extendedProps.event_type].color
                      }" id="event-name">${info.event.title}</div>
                      ${
                        info.event.extendedProps["file.uri"]
                          ? '<img id="event-img" src="' + event_img + '">'
                          : ""
                      }
                      <div id="event-time"><i class="fa fa-clock-o"></i>&nbsp;<span id="event-time-text">${
                        formatAMPM(event_start) + " to " + formatAMPM(event_end)
                      }</span></div>
                      <div id="event-location"><i class="fa fa-map-marker-alt"></i>&nbsp;<span id="event-location-text">${event_location}</span></div>
                      `;
  
            let tooltip = new tippy(info.el, {
              interactive: true,
              delay: 300,
              theme: "fcvic",
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
  
        const event_id = eventClickInfo.event.id;
        let event_container = document.getElementById(
          "civicrm-ux-event-popup-container"
        );
        let popup = document.getElementById("civicrm-ux-event-popup");
        event_container.style.display = "none";
        calendarEl.style.display = "none";
        popup.style.display = "block";
  
        let header = document.getElementById("civicrm-ux-event-popup-header");
        let summary = document.getElementById("civicrm-ux-event-popup-summary");
        let desc = document.getElementById("civicrm-ux-event-popup-desc");
        let register = document.getElementById("civicrm-ux-event-popup-register");
        let img = document.getElementById("civicrm-ux-event-popup-img");
        let event_type = document.getElementById(
          "civicrm-ux-event-popup-eventtype"
        );
        let event_loc = document.getElementById(
          "civicrm-ux-event-popup-location-txt"
        );
        let event_cpd = document.getElementById(
          "civicrm-ux-event-popup-cpd-points"
        );
        let event_time_day = document.getElementById(
          "civicrm-ux-event-popup-time-day"
        );
        let event_time_hours = document.getElementById(
          "civicrm-ux-event-popup-time-hours"
        );
  
        let event_location = eventClickInfo.event.extendedProps.street_address
          ? eventClickInfo.event.extendedProps.street_address + ", "
          : "";
        event_location += eventClickInfo.event.extendedProps.country
          ? eventClickInfo.event.extendedProps.country
          : "";
  
        if (eventClickInfo.event.extendedProps.zoom == "1") {
          event_location = eventClickInfo.event.extendedProps.country
            ? "Online, " + eventClickInfo.event.extendedProps.country
            : "Online";
        }
  
        const event_start = new Date(eventClickInfo.event["start"]);
        const event_end = new Date(eventClickInfo.event["end"]);
        header.innerHTML = eventClickInfo.event["title"];
        event_type.style.backgroundColor =
          eventClickInfo.el.firstChild.style.backgroundColor;
        summary.innerHTML = eventClickInfo.event.extendedProps["summary"];
        desc.innerHTML = eventClickInfo.event.extendedProps["description"];
        img.src =
          wp_site_obj.upload.baseurl +
          "/" +
          eventClickInfo.event.extendedProps["file.uri"];
        event_type.innerHTML = eventClickInfo.event.extendedProps.level
          ? eventClickInfo.event.extendedProps.level + " SESSION"
          : eventClickInfo.event.extendedProps["event_type"];
        event_type.style.backgroundColor =
          colors[eventClickInfo.event.extendedProps.event_type].color;
        event_time_day.innerHTML =
          days[event_start.getDay()] +
          ", " +
          event_start.getDate() +
          " " +
          months[event_start.getMonth()] +
          " " +
          event_start.getFullYear();
        event_loc.innerHTML = event_location;
        event_time_hours.innerHTML =
          formatAMPM(event_start) + " to " + formatAMPM(event_end);
        register.onclick = function () {
          window.location.href = eventClickInfo.event.url;
        };
        event_cpd.innerHTML =
          eventClickInfo.event.extendedProps.cpd_points > 0
            ? '<i class="fa fa-check-square"></i>&nbsp;&nbsp;' +
              eventClickInfo.event.extendedProps.cpd_points +
              " CPD Points"
            : "";
        event_cpd.innerHTML += eventClickInfo.event.extendedProps.category.length > 0
          ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-flag"></i>&nbsp;&nbsp;' +
            eventClickInfo.event.extendedProps.category.join([separator = ', '])
          : "";
  
        if (eventClickInfo.event.extendedProps["file.uri"]) {
          img.style.display = "block";
        } else {
          img.style.display = "none";
        }
        event_container.style.display = "block";
      },
    };
  
    calendarEl.dispatchEvent(
      domevent("fullcalendar:buildparams", calendarParams)
    );
  
    const calendar = new FullCalendar.Calendar(calendarEl, calendarParams);
  
    calendarEl.dispatchEvent(domevent("fullcalendar:prerender"));
    calendar.render();
  
    let sortByTypeBtn = document.querySelector(".fc-sortByType-button");
    let parent = sortByTypeBtn.parentElement;
    let sortByTypeSelect = document.createElement("select");
    sortByTypeSelect.classList.add("fc-button");
    sortByTypeSelect.classList.add("fc-button-primary");
    sortByTypeSelect.innerHTML = `<option selected="" value="all">sort by type</option>
      <option value="CPD">CPD</option><option value="Board meeting">Board meeting</option>
      <option value="Agency Managers meeting">Agency Managers meeting</option><option value="Working Group">Working Group</option>
      <option value="Networks">Networks</option><option value="Conference">Conferences</option><option value="Meeting">Meeting</option>`;
    sortByTypeSelect.setAttribute("id", "event-selector");
  
    sortByTypeBtn.style.display = "none";
    parent.appendChild(sortByTypeSelect);
  
    let selector = document.querySelector("#event-selector");
  
    selector.addEventListener("change", function () {
      calendar.refetchEvents();
    });
  
    let event_popup_x = document.getElementById("civicrm-ux-event-popup-close");
    if (event_popup_x) {
      event_popup_x.addEventListener("click", function () {
        document.getElementById("civicrm-ux-event-popup").style.display = "none";
        calendarEl.style.display = "flex";
      });
    }
  });
  