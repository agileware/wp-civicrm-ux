<?php

/**
 * Class Civicrm_Ux_Shortcode_CiviCRM_Api4_Get
 */
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

		// If "id" attribute exists but isn't an integer, replace it with a GET parameter with that name.
		if ( array_key_exists( 'id', $atts ) && ! is_int( $atts['id'] ) && preg_match('{^ (?![_-]) [A-Za-z0-9_-]+ $}x', $atts['id']) ) {
			$atts['id'] = (int) $_GET[ $atts['id'] ];
			if ( $atts['id'] < 1 ) {
				return __( 'Invalid ID' );
			}
		}

		// default checkPermissions as FALSE, assume that security is handled by appropriate API usage.
		$params = [ 'checkPermissions' => FALSE ];

		$atts = apply_filters( $this->get_shortcode_name() . '/attributes', $atts );

		// Interpret attributes as where clauses.
		foreach ( $atts as $k => $v ) {
			$v = html_entity_decode( preg_replace_callback( '/^( ( " ) | \' )(?<value>.*)(?(2) " | \' )$/', function ( $matches ) {
				return $matches['value'];
			}, $v ) );
			// Replace ?<parameter> with the escaped value of the matching URL parameter.
			$v = preg_replace_callback( '{\? (?<value> [[:alnum:]_-]+ )}x', function ( $matches ) {
				return str_replace( [ '%' ], [ '\%' ], CRM_Core_DAO::escapeString( $_GET[ $matches['value'] ] ?? '' ) );
			}, $v );
			$k = preg_replace( '/-(\w+)/', ':$1', $k );

			switch ( $k ) {
				case 'entity':
					break;
				case 'limit':
				case 'offset':
					$params[ $k ] = (int) $v;
					break;
				case 'checkpermissions':
				case 'check_permissions':
					$params[ 'checkPermissions' ] = (bool) $v;
					break;
				case 'sort':
				case 'orderby':
					[ $sort, $dir ] = explode( ':', $v, 2 );
					if ( $dir != 'DESC' ) {
						$dir = 'ASC';
					}
					$params['orderBy'][ $sort ] = $dir;
					break;
				case 'json':
					$unescaped = str_replace(array('&#91','&#93', '&quot', '&#34', '&#39'), array('[',']','"','"',"'"), $v);
					$json_array = json_decode($unescaped, true);
					foreach ( $json_array as $json_key => $json_value ) {
						$params[$json_key] = $json_value;
					}
					break;
				default:
					[ $op, $value ] = explode( ':', $v, 2 );
					if ( ! $value ) {
						$value = $op;
						$op    = '=';
					}

					if ( preg_match( '{(^|\.|_) id $}x', $k ) &&
					     preg_match( '{^\s* \d+ (\s* , \s* \d+)+ \s* $}x', $value ) ) {
						$op = [ '!=' => 'NOT IN', '=' => 'IN' ][ $op ] ?? $op;
					}

					if ( $op == 'IN' || $op == 'NOT IN' ) {
						$value = array_map( 'trim', explode( ',', $value ) );
					}

					switch ( $k ) {
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

		if ( preg_match_all( $output_regex, $content, $match ) ) {
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

			$trkey = $this->get_shortcode_name() . '__' . $post_revision . md5( $atts['entity'] . ':get:' . json_encode( $params ) );

			$all = $_GET['reset'] ? FALSE : get_transient( $trkey );

			if ( $all !== FALSE ) {
				return $all;
			}

			$all = '';

			$fields = civicrm_api4( $atts['entity'], 'getfields', [
				'checkPermissions' => FALSE,
				'select'           => [ 'name', 'data_type', 'fk_entity' ],
			] )->indexBy( 'name' );

			$results = civicrm_api4( $atts['entity'], 'get', $params );

			foreach ( $results as $result ) {
				$output = preg_replace_callback( $output_regex, function ( $match ) use ( $result, $fields ) {
					$output = $result[ $match['field'] ] ?? '';

					if ( ! $output ) {
						return '';
					}

					$field = $fields[ $match['field'] ] ?? [];

					if ( ( $field['data_type'] == 'Date' ) || ( $field['data_type'] == 'Timestamp' ) ) {
						$output = isset( $match['format'] ) ? strftime( $match['format'], strtotime( $output ) ) : CRM_Utils_Date::customFormat( $output );
					} elseif ( $field['fk_entity'] == 'File' ) {
						$output = Civicrm_Ux::in_basepage( function () use ( $output ) {
							return htmlentities( civicrm_api3( 'Attachment', 'getvalue', [
								'id'     => (int) $output,
								'return' => 'url',
							] ) );
						} );

						if ( preg_match( '/^img( : (?<w> \d+ %? ) x (?<h> \d+ %? ) | : alt= (?<alt>.*) | : [^:]* )* /x', $match['format'], $m ) ) {
							$output = '<img src="' . $output . '"'
							          . ( $m['w'] ? " width=\"${m['w']}\" height=\"${m['h']}\"" : '' ) .
							          ' alt="' . ( $m['alt'] ? htmlentities( $m['alt'] ) : '" role="presentation' ) .
							          '">';
						}
					} else {
						if ( is_array( $output ) ) {
							$output = implode( ', ', $output );
						}
						if ( strcasecmp( $match['format'], 'br' ) === 0 ) {
							$output .= '<br />';
						}
					}

					return apply_filters( 'esc_html', wp_check_invalid_utf8( $output ) );
				}, shortcode_unautop( $content ) );

				$output = apply_filters( $this->get_shortcode_name() . '/output', $output, $result, $params, $atts );

				$all .= do_shortcode( $output );
			}

			$all = trim( $all );

			set_transient( $trkey, $all, 4 * HOUR_IN_SECONDS );

			return $all;
		}
		catch ( API_Exception $e ) {
			\Civi::log()
			     ->error( 'WordPress Post ID: ' . get_the_ID() . '; CiviCRM APIv4 Shortcode: ' . $this->get_shortcode_name() . '; Params: ' . json_encode( $params ) . ';' );
			\Civi::log()
			     ->error( $e->getMessage() );
			\Civi::log()
			     ->error( $e->getTraceAsString() );

			return '';
		}
	}
}
