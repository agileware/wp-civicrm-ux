<?php
/**
 * Class Civicrm_Ux_Shortcode_CiviCRM_Api4_Get
 */

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Civicrm_Ux_Shortcode_CiviCRM_Api4_Get extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_cv_api4_get';
	}

	/**
	 * @param   array   $atts
	 * @param   null    $content
	 * @param   string  $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = NULL, $tag = '' ) {
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$atts = $atts + [
				'entity' => 'Contact',
			];

		// This shortcode only queries Contact and Event. Those are the entities it is designed and
		// documented for, and the two it handles safely: Contact runs with permission checks, and
		// Event is a public events listing. Every other entity ran with permission checks off and
		// returned whole tables - contributions, memberships, notes and so on - to whoever could
		// see the page, with no real use case. Entity names are accepted in any case and
		// canonicalised to the allowed spelling. A site that genuinely needs another entity can add
		// it with the ux_cv_api4_get/allowed_entities filter, accepting responsibility for it.
		$allowed_entities = (array) apply_filters( $this->get_shortcode_name() . '/allowed_entities', [ 'Contact', 'Event' ] );

		$entity = NULL;
		foreach ( $allowed_entities as $allowed_entity ) {
			if ( strcasecmp( $atts['entity'], $allowed_entity ) === 0 ) {
				$entity = $allowed_entity;
				break;
			}
		}

		if ( $entity === NULL ) {
			error_log( sprintf( 'ux_cv_api4_get: entity "%s" is not allowed; only %s may be queried', $atts['entity'], implode( ', ', $allowed_entities ) ) );
			return '';
		}

		$atts['entity'] = $entity;

		// If "id" attribute exists but isn't an integer, replace it with a GET parameter with that name.
		if ( array_key_exists( 'id', $atts ) &&
			 !filter_var( $atts['id'], FILTER_VALIDATE_INT, [ 'options' => [ 'min-range' => 1 ] ] ) &&
			 preg_match('{^ (?![_-]) [A-Za-z0-9_-]+ $}x', $atts['id']) ) {
			$atts['id'] = filter_input(INPUT_GET, $atts['id'], FILTER_VALIDATE_INT, [ 'option' => [ 'min-range' => 1 ] ]);
			// Not able to get id
			if ( $atts['id'] < 1 ) {
				return '';
			}
		}

		// Default checkPermissions by entity, overridable with the check_permissions attribute.
		// Event defaults to unchecked, because an events listing or calendar shows public events to
		// anonymous visitors, who usually lack the "access CiviEvent" permission. Everything else
		// (Contact, and any entity a site adds through the filter above) defaults to checked.
		$params = [ 'checkPermissions' => ( $atts['entity'] !== 'Event' ) ];

		// cache results by default
		$cache_results = true;

		$atts = apply_filters( $this->get_shortcode_name() . '/attributes', $atts );

		// Interpret attributes as where clauses.
		foreach ( $atts as $k => $v ) {
			$v = html_entity_decode( preg_replace_callback( '/^( ( " ) | \' )(?<value>.*)(?(2) " | \' )$/', function ( $matches ) {
				return $matches['value'];
			}, $v ) );
			// Replace ?<parameter> with the escaped value of the matching URL parameter.
			[$v, $default] = explode('??', $v, 2) + [ null, null ];
			$v = preg_replace_callback( '{\? (?<value> [[:alnum:]_-]+ )}x', function ( $matches ) use( $default ) {
				$get_value = isset($_GET[ $matches['value'] ]) ? sanitize_text_field($_GET[ $matches['value'] ]) : $default;
				return str_replace( [ '%' ], [ '\%' ], CRM_Core_DAO::escapeString( $get_value ) );
			}, $v );
			$k = preg_replace( '/-(\w+)/', ':$1', $k );

			switch ( $k ) {
				case 'entity':
					break;
				case 'cacheresults':
				case 'cache_results':
					$cache_results = filter_var($v, FILTER_VALIDATE_BOOLEAN);
					break;
				case 'limit':
				case 'offset':
					$params[ $k ] = (int) $v;
					break;
				case 'checkpermissions':
				case 'check_permissions':
					$params[ 'checkPermissions' ] = filter_var($v, FILTER_VALIDATE_BOOLEAN);
					break;
				case 'sort':
				case 'orderby':
					[ $sort, $dir ] = str_contains($v, ':') ? explode( ':', $v, 2 ) : [ $v, 'ASC' ];
					if ( $dir != 'DESC' ) {
						$dir = 'ASC';
					}
					$params['orderBy'][ $sort ] = $dir;
					break;
				default:
					[ $op, $value ] = str_contains($v, ':') ? explode( ':', $v, 2 ) : [ '=', $v ];

					if ( preg_match( '{(^|\.|_) id $}x', $k ) &&
					     preg_match( '{^\s* \d+ (\s* , \s* \d+)+ \s* $}x', $value ) ) {
						$op = [ '!=' => 'NOT IN', '=' => 'IN' ][ $op ] ?? $op;
					}

					if ( $op == 'IN' || $op == 'NOT IN' ) {
						$value = array_map( 'trim', explode( ',', $value ) );
					}

					switch ( $k ) {
						case 'my_events':
							// Only get events for the current logged in user
							if ($value && $atts['entity'] == 'Event') {
								// Cast to int so a visitor with no CiviCRM contact filters on 0,
								// which matches no participant. This clause is the only thing
								// keeping one member's registrations private, so it should not
								// rest on how the API happens to treat a NULL comparison.
								$contact_id = (int) CRM_Core_Session::singleton()->getLoggedInContactID();
								$this->add_participant_join( $params );
								$params['where'][] = ['participant.contact_id', '=', $contact_id];
							}
							break;
						case 'participant_status_id':
							if ($value && $atts['entity'] == 'Event') {
								$this->add_participant_join( $params );
								$params['where'][] = ['participant.status_id', $op, $value];
							}
							break;
						case 'event_type':
						case 'financial_type':
						default:
							$params['where'][] = [ $k, $op, $value ];
							break;
					}
					break;
			}
		}

		$match = [];

		$output_regex = '/ (?: ( \[ ) | ( {{ ) ) api4: (?<field> [^][[:space:]:{}]+ (?::(?:label|value|name|id))?) (?: : (?<format> [^][{}]+ ) )? (?(1) \] | }} ) /sx';

		if ( !is_null($content) && preg_match_all( $output_regex, $content, $match ) ) {
			$params['select'] = array_values( $match['field'] );
		}

		$params = apply_filters( $this->get_shortcode_name() . '/params', $params, $atts );

		if ($atts['entity'] == 'Contact') {
			$params['join'] = [
				['Email AS email', 'LEFT', ['email.is_primary', '=', TRUE]],
				['Address AS address', 'LEFT', ['address.is_primary', '=', TRUE]],
				['Phone AS phone', 'LEFT', ['phone.is_primary', '=', TRUE]],
			];
		}

		try {
			$post_id = get_post()->ID;
			$post_revision = wp_get_post_revision( $post_id );
			if ( $post_revision instanceof WP_Post ) {
				$post_revision = $post_revision->ID . '__';
			} else {
				$post_revision = '';
			}

			// Results fetched with permission checks vary by viewer, so the cache key
			// must vary too - otherwise the first permitted visitor warms a site-wide
			// transient that every later visitor, anonymous ones included, is served.
			// Both identities are used because either can carry the permissions that
			// shaped the result. Unchecked calls keep their original key, so public
			// queries stay shared and existing caches remain valid.
			$cache_identity = empty( $params['checkPermissions'] ) ? '' :
				':user:' . get_current_user_id() . ':contact:' . (int) CRM_Core_Session::singleton()->getLoggedInContactID();

			// The key also varies by post and by the template in $content: two shortcodes with the
			// same params but different output templates must not share a cache entry, and a
			// preview on one post must not overwrite the rendered output cached for another.
			$trkey = $this->get_shortcode_name() . '__' . $post_revision . md5( $atts['entity'] . ':get:' . json_encode( $params ) . $cache_identity . ':post:' . $post_id . ':tpl:' . (string) $content );

			$all = !empty($_GET['reset']) || !$cache_results ? FALSE : get_transient( $trkey );

			if ( $all !== FALSE ) {
				return $all;
			}

			$all = '';

			$fields = civicrm_api4( $atts['entity'], 'getfields', [
				'checkPermissions' => FALSE,
				'select'           => [ 'name', 'data_type', 'fk_entity', 'input_type' ],
			] )->indexBy( 'name' );

			$results = civicrm_api4( $atts['entity'], 'get', $params );

			// Output a no results message from a template part
			if ( $results->count() < 1 ) {
				// Buffer the output
				ob_start();
				civicrm_ux_load_template_part( 'shortcode', 'no-results' );
				return ob_get_clean();
			}

			foreach ( $results as $result ) {
				$output = preg_replace_callback( $output_regex, function ( $match ) use ( $result, $fields, $params ) {
					$output = $result[ $match['field'] ] ?? '';

					if ( ! $output ) {
						return '';
					}

					$field = $fields[ $match['field'] ] ?? [];
					$append_br = FALSE;
					// Rich text fields (e.g. Event description, RichTextEditor custom fields) hold
					// HTML that staff entered in CiviCRM, so render it as markup filtered through
					// wp_kses_post rather than escaping it to literal text. The ":html" format
					// opts any other field in, e.g. a TextArea that holds HTML.
					$is_html = ( ( $field['input_type'] ?? NULL ) === 'RichTextEditor' )
					           || strcasecmp( $match['format'] ?? '', 'html' ) === 0;

					if ( array_key_exists( 'data_type', $field ) && ( ( $field['data_type'] == 'Date' ) || ( $field['data_type'] == 'Timestamp' ) ) ) {
						$output = isset( $match['format'] ) ? Civicrm_Ux::getInstance()->strftime( $match['format'], strtotime( $output ) ) : CRM_Utils_Date::customFormat( $output );
					} elseif ( ( $field['fk_entity'] ?? NULL ) == 'File' ) {
						try {
							$output = $this->getAttachmentUrl( $output );

							if ( $output && preg_match( '/^img( : (?<w> \d+ %? ) x (?<h> \d+ %? ) | : alt= (?<alt>.*) | : [^:]* )* /x', $match['format'], $m ) ) {
								// Intentional image markup. The src is a signed CiviCRM file URL and
								// the dimensions and alt come from the shortcode format; escape each
								// and return the markup directly so it is not re-escaped as text.
								$img = '<img src="' . esc_url( $output ) . '"';
								if ( ! empty( $m['w'] ) ) {
									$img .= ' width="' . esc_attr( $m['w'] ) . '" height="' . esc_attr( $m['h'] ) . '"';
								}
								$img .= ' alt="' . ( ! empty( $m['alt'] ) ? esc_attr( $m['alt'] ) : '' ) . '"'
								        . ( empty( $m['alt'] ) ? ' role="presentation"' : '' ) . '>';

								return $img;
							}
						} catch (\CRM_Core_Exception $e) {
							\Civi::log()->error( 'WordPress Post ID: ' . get_the_ID() . '; CiviCRM APIv4 Shortcode: ' . $this->get_shortcode_name() . '; Params: ' . json_encode( $params ) . ';' );
							\Civi::log()->error( $e->getMessage() );
							\Civi::log()->error( $e->getTraceAsString() );
						}
					} else {
						if ( is_array( $output ) ) {
							$output = implode( ', ', $output );
						}
						if ( strcasecmp( $match['format'] ?? '', 'br' ) === 0 ) {
							$append_br = TRUE;
						}
					}

					// Escape the field value, and neutralise shortcode brackets so a stored value
					// cannot inject markup or a shortcode when the assembled template is run through
					// do_shortcode() below. (apply_filters('esc_html', ...) did neither - no core
					// callback is attached to that filter name, so it was a no-op.)
					//
					// HTML fields are filtered with wp_kses_post instead (scripts, event handlers and
					// other unsafe markup are stripped) and get the same bracket neutralisation.
					$output = wp_check_invalid_utf8( (string) $output );
					$output = $is_html ? wp_kses_post( $output ) : esc_html( $output );
					$output = str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], $output );

					return $append_br ? $output . '<br />' : $output;
				}, shortcode_unautop( $content ) );

				$output = apply_filters( $this->get_shortcode_name() . '/output', $output, $result, $params, $atts );

				$all .= do_shortcode( $output );
			}

			$all = trim( $all );

			if ( $cache_results ) {
				set_transient( $trkey, $all, 4 * HOUR_IN_SECONDS );
			}

			return $all;
		}
		catch ( API_Exception $e ) {
			\Civi::log()
			     ->error( 'WordPress Post ID: ' . get_the_ID() . '; CiviCRM APIv4 Shortcode: ' . $this->get_shortcode_name() . '; Params: ' . json_encode( $params ) . ';' );
			\Civi::log()
			     ->error( $e->getMessage() );
			\Civi::log()
			     ->error( $e->getTraceAsString() );

			// Buffer the output
			ob_start();
			civicrm_ux_load_template_part( 'shortcode', 'no-results' );
			return ob_get_clean();
		}
	}

	/**
	 * Join Participant for the my_events and participant_status_id attributes.
	 *
	 * Both attributes need the same alias, so the join is added at most once -
	 * repeating it would emit a duplicate alias when they are used together.
	 *
	 * @param array $params APIv4 params, modified in place.
	 */
	protected function add_participant_join( array &$params ) {
		$join = [ 'Participant AS participant', 'LEFT', [ 'participant.event_id', '=', 'id' ] ];

		if ( ! in_array( $join, $params['join'] ?? [], TRUE ) ) {
			$params['join'][] = $join;
		}
	}

	protected function getAttachmentUrl( int $id ): string {
		$file = \Civi\Api4\File::get(FALSE)
		                       ->addWhere('id', '=', $id)
		                       ->execute()
		                       ->first();

		if(!$file) {
			return '';
		}

		$fileHash = \CRM_Core_BAO_File::generateFileHash(NULL, $file['id']);

		$url = (string) Civicrm_Ux::in_basepage(
			fn() => CRM_Utils_System::url( 'civicrm/file', [ 'reset' => 1, 'id' => $file['id'], 'fcs' => $fileHash ] )
		);

		return $url;
	}
}
