<?php

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Civi\Api4\CustomField;
use Civi\Api4\Event;

class Civicrm_Ux_Shortcode_Event_FullCalendar extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * The image field the calendar asks for when the shortcode names none. It is fixed, so the
	 * REST endpoint can honour it without a signature - see verifyFieldConfig().
	 */
	const DEFAULT_IMAGE_SRC_FIELD = 'file.uri';

	/**
	 * Event fields the calendar never publishes, even when a shortcode names them: they hold the
	 * names and addresses CiviCRM sends registration confirmations from and copies them to.
	 */
	const PRIVATE_EVENT_FIELDS = [ 'confirm_from_name', 'confirm_from_email', 'cc_confirm', 'bcc_confirm' ];

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_fullcalendar';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		$colors_arr = array();

		// Sanitize shortcode parameters. Every check below is guarded by isset(), so this runs for
		// any attributes at all: a count($atts) > 1 guard skipped shortcodes with a single attribute.
		if (!empty($atts)) {
			if (isset($atts['types'])) {
				$types_tmp = explode(",", $atts['types']);
				for ($i = 0; $i < count($types_tmp); $i++) {
					$types_tmp[$i] = preg_replace('/[^a-zA-Z0-9 ]/', '', $types_tmp[$i]);
				}
				$atts['types'] = implode(",", $types_tmp);
			}
			if (isset($atts['colors'])) {
				$colors_tmp = explode(",", $atts['colors']);
				foreach ($colors_tmp as $color) {
					array_push($colors_arr, sanitize_hex_color_no_hash($color));
				}
			}
			if (isset($atts['force_login'])) {
				$atts['force_login'] = filter_var($atts['force_login'], FILTER_VALIDATE_BOOLEAN);
			}
			if (isset($atts['redirect_after_login'])) {
				$atts['redirect_after_login'] = sanitize_text_field($atts['redirect_after_login']);
			}

		}

		// Shortcode parameters defaults
		$wporg_atts = shortcode_atts(
			array(
                'types' => NULL,
                'colors' => NULL,
				'force_login' => FALSE,
				'start' => date('Y-m-d', strtotime('-1 year')),
				'image_src_field' => static::DEFAULT_IMAGE_SRC_FIELD,
				'extra_fields' => ''
			), $atts, $tag
		);

		// Sanitize all $wporg_atts parameters
		$wporg_atts['types'] = sanitize_text_field($wporg_atts['types']);
		$wporg_atts['colors'] = sanitize_text_field($wporg_atts['colors']);
		$wporg_atts['force_login'] = rest_sanitize_boolean($wporg_atts['force_login']);
		$wporg_atts['start'] = sanitize_text_field($wporg_atts['start']);
		$wporg_atts['image_src_field'] = sanitize_text_field($wporg_atts['image_src_field']);
		$wporg_atts['extra_fields'] = sanitize_text_field($wporg_atts['extra_fields']);

		// extra_fields and image_src_field name the Event fields the REST endpoint selects, and the
		// browser sends them back with each request. Keep only the fields that are safe to publish,
		// then sign the result: the endpoint ignores any set that does not carry this signature.
		$requested_fields = array_filter( array_map( 'trim', explode( ',', $wporg_atts['extra_fields'] ) ) );
		$extra_fields = static::allowedFields( $requested_fields );
		foreach ( array_diff( $requested_fields, $extra_fields ) as $rejected ) {
			error_log( sprintf( 'ux_event_fullcalendar: extra_fields "%s" is not an Event field the calendar may publish, and was ignored', $rejected ) );
		}
		$wporg_atts['extra_fields'] = implode( ',', $extra_fields );

		$requested_image_field = $wporg_atts['image_src_field'];
		$wporg_atts['image_src_field'] = static::allowedImageSrcField( $requested_image_field ) ?? '';
		if ( $requested_image_field !== '' && $wporg_atts['image_src_field'] === '' ) {
			error_log( sprintf( 'ux_event_fullcalendar: image_src_field "%s" is not an Event field the calendar may publish, and was ignored', $requested_image_field ) );
		}

		$wporg_atts['fields_sig'] = static::signFieldConfig( $wporg_atts['extra_fields'], $wporg_atts['image_src_field'] );

		$redirect_after_login = isset($atts['redirect_after_login']) ? $atts['redirect_after_login'] : '';

		$colors = [ 'default' => static::getDefaultColor() ];

        if(!empty($wporg_atts['types']) && !empty($wporg_atts['colors'])) {
            $types_arr = explode(',', $wporg_atts['types']);
            $limit = min(count($types_arr), count($colors_arr));
            for ($i = 0; $i < $limit; $i++) {
                $colors[$types_arr[$i]] = Civicrm_Ux_Validators::validateCssColor($colors_arr[$i]);
            }
        }

        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [] );
		wp_enqueue_style( 'ux-fullcalendar-styles', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/css/event-fullcalendar.css', [] );

		wp_enqueue_script( 'fullcalendar-base', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js', [] );
		wp_enqueue_script( 'popper', 'https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js', [] );
		wp_enqueue_script( 'tippy', 'https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.js', [ 'popper' ] );
		// Versioned by mtime: a browser holding a copy from before fields_sig would send no
		// signature, and the endpoint would silently drop the shortcode's extra_fields.
		wp_enqueue_script( 'ux-fullcalendar', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/js/event-fullcalendar.js', [ 'fullcalendar-base', 'popper', 'tippy', 'wp-api-request' ], filemtime( WP_CIVICRM_UX_PLUGIN_PATH . 'public/js/event-fullcalendar.js' ) );

        $ux_fullcalendar = [
            'ajax_url' => get_rest_url(),
            'redirect_after_login' => $redirect_after_login,
        ];

        if(!empty($colors)) {
            $ux_fullcalendar['colors'] = $colors;
        }

        $ux_fullcalendar = $ux_fullcalendar + array_filter($wporg_atts);

        if(!empty($ux_fullcalendar['types'])) {
            $ux_fullcalendar['filterTypes'] = explode(',', $ux_fullcalendar['types']);
        } else {
            $options = Event::getFields(FALSE)
                ->setLoadOptions([ 'name', 'label' ])
                ->addWhere('name', '=', 'event_type_id')
                ->addSelect('options')
                ->execute()
                ->first()['options'];

            $ux_fullcalendar['filterTypes'] = array_map(
                fn($_type) => $_type['label'],
            $options ?: []
            );
        }

        wp_localize_script( 'ux-fullcalendar', 'uxFullcalendar', $ux_fullcalendar );

		return '<div id="civicrm-event-fullcalendar" class="fullcalendar-container"></div>
		<div class="civicrm-ux-event-popup-container">
		<div class="civicrm-ux-event-popup">
			<button onclick="hideEventsCalendarPopup()" id="civicrm-ux-event-popup-close">&times;</button>
			<div id="civicrm-ux-event-popup-content"></div>
		</div></div>';
	}

    public static function getDefaultColor() {
        return apply_filters( 'ux_event_fullcalendar/default_color', 'transparent' );
    }

    public static function getDefaultForceLogin() {
        return apply_filters( 'ux_event_fullcalendar/force_login', false );
    }

	/**
	 * Reduce a list of Event field names to the ones the calendar may publish.
	 *
	 * The REST endpoint runs Event.get without permission checks, and APIv4 follows joins, so a
	 * name such as created_id.email_primary.email would return a contact's email address. A field
	 * is kept only when it is a column of Event itself (optionally with a pseudoconstant suffix
	 * such as :label) and not in PRIVATE_EVENT_FIELDS, or an active custom field in an active
	 * custom group. Sites can name further fields with the ux_event_fullcalendar/allowed_fields
	 * filter.
	 *
	 * The custom group's is_public flag is deliberately not consulted: sites leave it off on groups
	 * they publish on the calendar, and the signature already stops a visitor choosing fields. The
	 * page author's choice of fields is the control, as it was before the signature.
	 *
	 * @param string[] $fields
	 *
	 * @return string[] The allowed fields, in the order given
	 */
	public static function allowedFields( array $fields ): array {
		$fields = array_values( array_unique( array_filter( $fields, fn( $field ) =>
			Civicrm_Ux_Validators::validateAPIFieldName( $field, 'extra_fields' ) !== null
			&& preg_match( '/^[^:]+(?::(?:label|name|abbr|description))?$/', $field )
		) ) );

		if ( empty( $fields ) ) {
			return [];
		}

		$site_allowed = (array) apply_filters( 'ux_event_fullcalendar/allowed_fields', [] );
		$base_name    = fn( $field ) => explode( ':', $field, 2 )[0];

		// getFields() resolves join paths too, reporting them with the joined entity (Contact,
		// Email, ...), so the entity check below is what rejects a join.
		$meta = Event::getFields( FALSE )
			->addSelect( 'name', 'entity', 'custom_field_id' )
			->addWhere( 'name', 'IN', array_values( array_unique( array_map( $base_name, $fields ) ) ) )
			->execute()
			->indexBy( 'name' )
			->getArrayCopy();

		$custom_field_ids  = array_filter( array_column( $meta, 'custom_field_id' ) );
		$active_custom_ids = [];
		if ( ! empty( $custom_field_ids ) ) {
			$active_custom_ids = CustomField::get( FALSE )
				->addSelect( 'id' )
				->addWhere( 'id', 'IN', array_values( $custom_field_ids ) )
				->addWhere( 'is_active', '=', TRUE )
				->addWhere( 'custom_group_id.is_active', '=', TRUE )
				->execute()
				->column( 'id' );
		}

		return array_values( array_filter( $fields, function ( $field ) use ( $meta, $base_name, $site_allowed, $active_custom_ids ) {
			if ( in_array( $field, $site_allowed, TRUE ) ) {
				return TRUE;
			}

			$field_meta = $meta[ $base_name( $field ) ] ?? NULL;
			if ( ! $field_meta || $field_meta['entity'] !== 'Event' ) {
				return FALSE;
			}

			if ( ! empty( $field_meta['custom_field_id'] ) ) {
				return in_array( $field_meta['custom_field_id'], $active_custom_ids );
			}

			return ! in_array( $field_meta['name'], static::PRIVATE_EVENT_FIELDS, TRUE );
		} ) );
	}

	/**
	 * @param string $field The image_src_field the shortcode or request names
	 *
	 * @return string|null The field when the calendar may select it, otherwise null
	 */
	public static function allowedImageSrcField( string $field ): ?string {
		if ( $field === '' ) {
			return NULL;
		}

		if ( $field === static::DEFAULT_IMAGE_SRC_FIELD ) {
			return $field;
		}

		return static::allowedFields( [ $field ] )[0] ?? NULL;
	}

	/**
	 * Sign the field configuration a shortcode hands to the browser, so the REST endpoint can tell
	 * a configuration a page author chose from one a visitor typed into the URL.
	 *
	 * The signature covers the exact strings the browser sends back, and depends on nothing about
	 * the viewer, so a page served from a full-page cache still carries a valid one.
	 */
	public static function signFieldConfig( string $extra_fields, string $image_src_field ): string {
		return hash_hmac( 'sha256', "ux_event_fullcalendar\n{$extra_fields}\n{$image_src_field}", wp_salt( 'nonce' ) );
	}

	public static function verifyFieldConfig( string $extra_fields, string $image_src_field, string $signature ): bool {
		return $signature !== '' && hash_equals( static::signFieldConfig( $extra_fields, $image_src_field ), $signature );
	}
}
