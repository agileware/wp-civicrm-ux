const domevent = function(eventName, detail) {
    if (typeof Window.CustomEvent === "function") {
        return new CustomEvent(eventName, { bubbles: true, cancelable: true, detail });
    } else {
        const event = document.createEvent('CustomEvent');
        event.initCustomEvent(eventName, true, true, detail);
        return event;
    }
};

function formatAMPM(date) {
  let hours = date.getHours();
  let minutes = date.getMinutes();
  const ampm = hours >= 12 ? 'pm' : 'am';
  hours = hours % 12;
  hours = hours ? hours : 12;
  minutes = minutes < 10 ? '0'+minutes : minutes;
  const strTime = hours + ':' + minutes + ' ' + ampm;
  return strTime;
}

function extractUrlValue(key, url)
{
    if (typeof(url) === 'undefined')
        url = window.location.href;
    const match = url.match('[?&]' + key + '=([^&]+)');
    return match ? match[1] : null;
}

const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('civicrm-event-fullcalendar');
    let sources_api_obj = {};

    const sources = {
        meetings: {
            url: wp_site_obj.upload.baseurl + '/' + wp_site_obj.meetings,
            format: "ics",
            category: "meeting",
            color: "red",
            textColor: "white"
        },
        fundraiser: {
            url: wp_site_obj.upload.baseurl + '/' + wp_site_obj.fundraisers, 
            format: "ics",
            category: "fundraiser",
            color: "yellow",
            textColor: "black"
        }
    }

    let calendarParams = ({
        initialView: 'dayGridMonth',
        eventSources: [sources.fundraiser, sources.meetings],
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            omitZeroMinute: false,
            meridiem: 'short'
        },
        customButtons: {
            sortByType: {
                text: 'sort by type'
            },
        },
        headerToolbar: {
            left: 'dayGridMonth,listMonth sortByType',
            center: '',
            end: 'prevYear,prev title next,nextYear'
        },
        buttonText: {
            dayGridMonth: 'Events',
            prevYear: '« Previous year',
            prev: '‹ Previous month',
            nextYear: 'Next year »',
            next: 'Next month ›'
        },
        buttonIcons: {
            prevYear: 'chevrons-left',
            prev: 'chevron-left',
            nextYear: 'chevrons-right',
            next: 'chevron-right'
        },
        firstDay: 1,
        eventContent: function(arg) {
            let sortByTypeBtn = document.querySelector('.fc-sortByType-button');
            if (sortByTypeBtn) {
                sortByTypeBtn.remove();
            }

            let eventHolder = document.createElement('div');
            eventHolder.classList.add('event-holder');
            eventHolder.style.backgroundColor = arg.backgroundColor;
            eventHolder.style.color = arg.textColor;
            eventHolder.innerHTML = '<div class="fc-event-time">' + arg.timeText + '</div><div class="fc-event-title"><a style="text-decoration: none; color: ' + arg.textColor + ';" href="' + arg.event.url + '">' + arg.event.title + '</a></div>';

            let arrayOfDomNodes = [ eventHolder ]
            return { domNodes: arrayOfDomNodes }
        },

        eventDidMount: function(info) {
            let event_img;
            let event_title;
            let event_time;
            let event_location;

            const event_id = extractUrlValue('id', info.event.url);

            jQuery.ajax({
                method : "GET",
                dataType : "json",
                url : wp_site_obj.ajax_url,
                data : {action: "get_thumbnail", event_id : event_id},
                success: function(response) {



                    const event_start = new Date(response.result['start_date']);
                    const event_end = new Date(response.result['end_date']);

                    event_time = formatAMPM(event_start) + ' to ' + formatAMPM(event_end);
                    event_img = wp_site_obj.upload.baseurl + '/civicrm/custom/' + response.result['file.uri'];
                    event_title = response.result.title;
                    event_location = response.result['address.city'] + ' ' + response.result['address.country_id:name'];

                    const template = `<div class="tooltip">
                        <div id="event-name">${info.event.title}</div>
                        ${response.result['file.uri'] ? '<img id="event-img" src="' + event_img + '">' : ''}
                        <div id="event-time"><i class="fa fa-clock-o"></i><span id="event-time-text">${event_time}</span></div>
                        <div id="event-location"><i class="fa fa-map-marker-alt"></i><span id="event-location-text">${event_location}</span></div>
                        </div>`
                       

                    let tooltip = new Tooltip(info.el, {
                        html: true,
                        template: template,
                        title: info.event.title,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            })
            

        },

        eventClick: function(eventClickInfo) {
            let jsEvent = eventClickInfo.jsEvent;
            jsEvent.preventDefault();

            const event_id = extractUrlValue('id', eventClickInfo.event.url);

            jQuery.ajax({
                method : "GET",
                dataType : "json",
                url : wp_site_obj.ajax_url,
                data : {action: "get_event_info", event_id : event_id},
                success: function(response) {
                    let header = document.getElementById("civicrm-ux-event-popup-header");
                    let summary = document.getElementById("civicrm-ux-event-popup-summary");
                    let desc = document.getElementById("civicrm-ux-event-popup-desc");
                    let img = document.getElementById("civicrm-ux-event-popup-img");
                    let event_type = document.getElementById("civicrm-ux-event-popup-eventtype");
                    let event_loc = document.getElementById("civicrm-ux-event-popup-location");
                    let event_time_day = document.getElementById("civicrm-ux-event-popup-time-day");
                    let event_time_hours = document.getElementById("civicrm-ux-event-popup-time-hours");

                    const event_start = new Date(response.result['start_date']);
                    const event_end = new Date(response.result['end_date']);

                    header.innerHTML = response.result['title'];
                    summary.innerHTML = response.result['summary'];
                    desc.innerHTML = response.result['description'];
                    img.src = wp_site_obj.upload.baseurl + '/civicrm/custom/' + response.result['file.uri'];
                    event_type.innerHTML = response.result['event_type_id:name'];
                    event_time_day.innerHTML = days[event_start.getDay()] + ', ' + event_start.getDate() + ' ' + months[event_start.getMonth()] + ' ' + event_start.getFullYear();
                    event_time_hours.innerHTML = formatAMPM(event_start) + ' to ' + formatAMPM(event_end);
                }
            })

            let popup = document.getElementById("civicrm-ux-event-popup");
            calendarEl.style.display = "none";
            popup.style.display = "block";
        }
    });

    calendarEl.dispatchEvent(domevent('fullcalendar:buildparams', calendarParams));

    const calendar = new FullCalendar.Calendar(calendarEl, calendarParams);

    let sources_tmp = calendar.getEventSources();
    for (let i = 0; i < sources_tmp.length; i++) {
        let source = sources_tmp[i];
        sources_api_obj[source.internalEventSource.extendedProps.category] = source;
    }

    calendarEl.dispatchEvent(domevent('fullcalendar:prerender'));
    calendar.render();

    let sortByTypeBtn = document.querySelector('.fc-sortByType-button');
    let parent = sortByTypeBtn.parentElement;
    let sortByTypeSelect = document.createElement('select');
    sortByTypeSelect.classList.add('fc-button');
    sortByTypeSelect.classList.add('fc-button-primary');
    sortByTypeSelect.innerHTML = `<option selected="" value="all">sort by type</option>
    <option value="conference">Conference</option><option value="meeting">Meeting</option>
    <option value="fundraiser">Fundraiser</option><option value="workshop">Workshop</option>
    <option value="performance">Performance</option><option value="exhibition">Exhibition</option>`;
    sortByTypeSelect.setAttribute("id", "event-selector");

    sortByTypeBtn.style.display = "none";
    parent.appendChild(sortByTypeSelect);

    let selector = document.querySelector("#event-selector");

    selector.addEventListener('change', function() {
        let val = selector.value;

        if (!(val == "all")) {
            let sources = calendar.getEventSources();
            let foundType = false;
            for (let i = 0; i < sources.length; i++) {
                let source = sources[i];
                let cat = source.internalEventSource.extendedProps.category
                if (cat != val) {
                    source.remove();
                } else {
                    foundType = true;
                }

                if (!foundType) {
                    calendar.addEventSource(sources_api_obj[val]);
                }
            }
        } else {
            for (let key in sources_api_obj) {
                if (sources_api_obj.hasOwnProperty(key)) {
                    calendar.addEventSource(sources_api_obj[key]);
                }
            }
        }

        calendar.refetchEvents();
    });

    let event_popup_x = document.getElementById("civicrm-ux-event-popup-close");
    if (event_popup_x) {
        event_popup_x.addEventListener("click", function(){
            document.getElementById("civicrm-ux-event-popup").style.display = "none";
            calendarEl.style.display = "flex";
        });    
    }
});
