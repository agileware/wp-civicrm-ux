document.addEventListener("DOMContentLoaded", () => {

    let selfServeChecksumForm = document.getElementById('ss-cs-form');

    if ( selfServeChecksumForm != null ) {

        let turnstile = document.querySelector('.cf-turnstile');
        let submitButton = document.getElementById('ss-cs-submit');

        if (turnstile != null) {
            submitButton.disabled = true;
        }

        selfServeChecksumForm.addEventListener('submit', () => {
            submitButton.disabled = true;
        });
    }
});