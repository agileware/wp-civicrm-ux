document.addEventListener("DOMContentLoaded", () => {
  // Select all buttons with the class 'event-cancel-registration'
  const buttons = document.querySelectorAll("button.event-cancel-registration");
  const wp_json_url = document.querySelector(
    'link[rel="https://api.w.org/"]'
  ).href;

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
        // Show the custom confirmation dialog
        const modal = document.getElementById("event-cancellation-confirm-dialog-" + event_id);

        // Get the buttons in the dialog
        const confirmYes = modal.querySelector(".confirm-yes");
        const confirmNo = modal.querySelector(".confirm-no");
        modal.showModal();

        // Handle the "Yes" button click
        confirmYes.onclick = async function () {
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
          }
        };

        // Handle the "No" button click
        confirmNo.onclick = function () {
          modal.close();
        };
      }
    });
  });
});
