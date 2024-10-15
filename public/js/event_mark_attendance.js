document.addEventListener("DOMContentLoaded", () => {
    // Select all buttons with the class 'event-mark-attendance'
    console.log('hi');
    const buttons = document.querySelectorAll("button.event-mark-attendance");
    const wp_json_url = document.querySelector('link[rel="https://api.w.org/"]').href;
  
    // Custom error message
    const error_message = document.querySelector(".event-markattendance-error");
  
    // Iterate over the NodeList and add an event listener to each button
    buttons.forEach((button) => {
      button.addEventListener("click", async (event) => {
        // app_wp_nonce is required for the request to be authenticated with WordPress
        // if app_wp_nonce is undefined just exit out
        if (typeof app_wp_nonce === "undefined") {
          return false;
        }
  
        let event_id = event.target.dataset.eventid;
  
        // Handle the click event
        if (event_id) {
          button.disabled = true;
  
          // Show the custom confirmation dialog
          const modal = document.getElementById("event-markattendance-confirm-dialog-" + event_id);

          // Get the form
          const form = modal.querySelector('form');
  
          // Get the buttons in the dialog
          const close = modal.querySelector(".close");
          modal.showModal();
  
          // Handle the "Submit" button click
          // Add event listener for the form submission
        form.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent the default form submission

            // Get the selected radio button value
            const attendance = form.querySelector('input[name="attendance"]:checked').value;
            const attended_status = form.querySelector('input[name="attended_status"]').value;
            const not_attended_status = form.querySelector('input[name="not_attended_status"]').value;

            // Proceed with the request
            // Call CiviCRM Form Processor, cancel_event_registration
            let url = wp_json_url + "civicrm_ux/mark-event-attendance/" + event_id + "/" + attendance + "/" + attended_status + "/" + not_attended_status;
  
            console.log(url);
            const response = await fetch(url, {
              headers: { "X-WP-Nonce": app_wp_nonce },
            });
  
            if (response.ok) {
                console.log(response);
              // reload the page
              location.reload(true);
            } else {
              // display an error
              const my_error_message = error_message.cloneNode(true);
              my_error_message.style.display = 'block';
              button.insertAdjacentElement('afterend', my_error_message);
            }

            modal.close();
        });

          /*submit.onclick = async function () {
            modal.close();
  
            // Proceed with the request
            // Call CiviCRM Form Processor, cancel_event_registration
            let url = wp_json_url + "civicrm_ux/cancel-event-registration/" + event_id;
  
            const response = await fetch(url, {
              headers: { "X-WP-Nonce": app_wp_nonce },
            });
  
            if (response.ok) {
              // reload the page
              location.reload(true);
            } else {
              // display an error
              const my_error_message = error_message.cloneNode(true);
              my_error_message.style.display = 'block';
              button.insertAdjacentElement('afterend', my_error_message);
            }
          };*/
  
          // Handle the "Close" button click
          close.onclick = function () {
            button.disabled = false;
            modal.close();
          };
        }
      });
    });
  });
  