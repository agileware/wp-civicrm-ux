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
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$atts = $atts + [
				'entity'  => 'Contact',
			];

		// If "id" attribute exists but isn't an integer, replace it with a GET parameter with that name.
		if( array_key_exists( 'id', $atts ) && !is_int( $atts['id'] ) ) {
			$atts['id'] = (int) $_GET[$atts['id']];
			if($atts['id'] < 1) {
				return __('Invalid ID');
			}
		}

		// default checkPermissions as FALSE, assume that security is handled by appropriate API usage.
		$params = [ 'checkPermissions' => FALSE ];

		// Interpret attributes as where clauses.
		foreach( $atts as $k => $v ) {
			$v = html_entity_decode(preg_replace_callback('/^( ( " ) | \' )(?<value>.*)(?(2) " | \' )$/', fn($matches) => $matches[value], $v));
			$k = preg_replace('/-(\w+)/', ':$1', $k);

			switch($k) {
				case 'entity':
					break;
				case 'limit':
				case 'offset':
					$params[$k] = (int) $v;
					break;
				case 'checkPermissions':
					$params[$k] = (bool) $v;
					break;
				case 'sort':
				case 'orderby':
					[ $sort, $dir ] = explode( ':', $v, 2 );
					if($dir != 'DESC')
						$dir = 'ASC';
					$params['orderBy'][$sort] = $dir;
					break;
				default:
					[ $op, $value ] = explode( ':', $v, 2 );
					if(!$value) {
						$value = $op;
						$op = '=';
					}

					if($op == 'IN' || $op == 'NOT IN') {
						$value = explode(',', $value);
					}

					switch($k) {
						case 'event_type':
						case 'financial_type':
						default:
							$params['where'][] = [$k, $op, $value];
							break;
					}
					break;
			}
		}

		$match = [];

		$output_regex = '/ (?: ( \[ ) | ( {{ ) ) api4: (?<field> [^][[:space:]:{}]+ ) (?: : (?<format>[^][{}]+ ) )? (?(1) \] | }} ) /sx';

		if( preg_match_all( $output_regex, $content, $match )) {
			$params['select'] = array_values($match['field']);
		}

		try {
			$trkey = $this->get_shortcode_name() . '__' . md5($atts['entity'] . ':get:' . json_encode($params));

			$all = get_transient( $trkey );

			if( $all !== FALSE ) {
				return $all;
			}

			$all = '';

			$class = "\\Civi\\Api4\\{$atts['entity']}";

			$fields = $class::getFields(FALSE)
			                ->addSelect( 'name', 'data_type', 'fk_entity' )
			                ->execute()
			                ->indexBy( 'name' );

			$results = civicrm_api4( $atts['entity'], 'get', $params );

			foreach($results as $result) {
				$output = preg_replace_callback( $output_regex, function( $match ) use( $result, $fields ){
					$output = $result[ $match[ 'field' ] ] ?? '';

					if(!$output) {
						return '';
					}

					$field = &$fields[ $match[ 'field' ] ];

					if( ($field[ 'data_type' ] == 'Date') || ($field[ 'data_type' ] == 'Timestamp') ) {
						$output = CRM_Utils_Date::customFormat($output, $match['format'] ?? NULL);
					}
					elseif( $field[ 'fk_entity' ] == 'File' ) {
						$output = Civicrm_Ux::in_basepage( function() use ($output) { return htmlentities( civicrm_api3( 'Attachment', 'getvalue', [ 'id' => (int) $output, 'return' => 'url' ] )); } );

						if( preg_match( '/^img( : (?<w> \d+ %? ) x (?<h> \d+ %? ) | : alt= (?<alt>.*) | : [^:]* )* /x', $match[ 'format' ], $m ) ) {
							$output = '<img src="' . $output . '"'
							          . ($m['w'] ? " width=\"${m['w']}\" height=\"${m['h']}\"" : '') .
							          ' alt="' . ($m['alt'] ? htmlentities($m['alt']) : '" role="presentation') .
							          '">';
						}
					}
					else {
						if ( is_array( $output ) ) {
							$output = implode( ', ', $output );
						}
						if ( strcasecmp( $match['format'], 'br') === 0) {
							$output .= '<br />';
						}
					}

					return apply_filters( 'esc_html', wp_check_invalid_utf8( $output ) );
				}, shortcode_unautop( $content ) );

				$all .= do_shortcode( $output );
			}

			$all = trim($all);

			set_transient($trkey, $all, 4 * HOUR_IN_SECONDS);

			return $all;
		} catch (API_Exception $e) {
			\Civi::log()->error('Error with API4 Shortcode on post #' . get_the_ID(). ': ' . $e->getMessage());

			return '';
		}
	}
}
