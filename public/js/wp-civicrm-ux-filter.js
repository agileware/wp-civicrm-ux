document.addEventListener("DOMContentLoaded", () => {
    // Get the filter form element
    const form = document.getElementById("wp-civicrm-ux-filter-form");

    // Get the current URL search params
    const params = new URLSearchParams(document.location.search);

    // Get select elements inside this form
    const selectFilters = form.querySelectorAll("select");

    selectFilters.forEach((element) => {
        // Set default value based on URL search params
        let inputName = element.getAttribute("name");
        let param = params.get(inputName);

        if (param) {
            element.value = param;
            element.dispatchEvent(new Event("change"));
        }

        // Auto-submit the form when this element changes
        element.addEventListener('change', function() {
            this.form.submit();
        });
    });

    /* Add additional form element types here */
});
