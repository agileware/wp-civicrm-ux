<?php
/**
 * Default template for the [ux_self_serve_checksum] shortcode.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */

if (
    ! $args['form_submitted'] ||
    ( $args['form_submitted'] && isset( $args['turnstile_passed'] ) && ! $args['turnstile_passed'] )
) :
?>
    <section class="ss-cs-form" aria-label="Protected Content Access Request Form">
        <div class="ss-cs-form-text">
            <?php echo wp_kses_post( $args['form_text'] ); ?>
        </div>
        <form id="ss-cs-form" method="post" data-turnstilepassed="<?php echo esc_attr( $args['turnstile_passed'] ? 'true' : 'false' ); ?>" novalidate>
            <fieldset>
                <legend class="screen-reader-text">Enter your email to receive your unique link</legend>

                <label for="ss-cs-email" class="ss-cs-email">Your email address:</label>
                <input 
                    type="email" 
                    id="ss-cs-email" 
                    name="ss-cs-email" 
                    required 
                    autocomplete="email" 
                    aria-describedby="ss-cs-email-desc"
                >
                <p id="ss-cs-email-desc" class="screen-reader-text">Enter the email address you used to register.</p>

                <input type="hidden" name="ss-cs-title" value="<?php echo esc_attr( get_the_title() ); ?>">
                <input type="hidden" name="ss-cs-url" value="<?php echo esc_url( $args['url'] ); ?>">

                <?php echo $args['turnstile']; ?>

                <button 
                    type="submit" 
                    name="ss-cs-submit" 
                    id="ss-cs-submit" 
                    <?php echo ! empty( $args['turnstile'] ) ? 'disabled aria-disabled="true"' : ''; ?>
                >
                    Submit
                </button>
            </fieldset>
        </form>
    </section>
    <?php if ( ! empty( $args['turnstile'] ) ) : ?>
        <script>
            let turnstileCompleted = false;
            
            // This function will be called when Turnstile completes successfully
            function onTurnstileComplete(token) {
                turnstileCompleted = true;

                let submitButton = document.getElementById('ss-cs-submit');
                submitButton.disabled = false;
            }
        </script>
    <?php endif; ?>
<?php endif; ?>