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

		// If "id" attritbute exists but isn't an integer, replaced it with a GET parameter with that name.
        if( array_key_exists( 'id', $atts ) && !is_int( $atts['id'] ) ) {
			$atts['id'] = (int) $_GET[$atts['id']];
			if($atts['id'] < 1) {
				return __('Invalid ID');
			}
        }

		$params = [];

		// Interpret attributes as where clauses.
		foreach( $atts as $k => $v ) {
			switch($k) {
				case 'entity':
					break;
				case 'limit':
					$params['limit'] = (int) $v;
					break;
				default:
					list($op, $value) = explode(':', $v, 2);
					if(!$value) {
						$value = $op;
						$op = '=';
					}
					$params['where'][] = [$k, $op, $value];
					break;
			}
		}

		$match = [];

		$output_regex = '{ \[ api4: (?<field> [^][:space:][]+ ) \] }sx';

		if( preg_match_all( $output_regex, $content, $match )) {
			foreach($match['field'] as $field) {
				$params['select'][] = $field;
			}
		}

		try { 
			$all = '';
			
			$results = civicrm_api4( $atts['entity'], 'get', $params );

			foreach($results as $r) {
				$output = preg_replace_callback( $output_regex, function( $m ) use( $r ){
					return apply_filters( 'esc_html', wp_check_invalid_utf8( $r[$m['field']] ?? '' ) );
				}, $content );
			
				$all .= do_shortcode( $output );
			}

			return trim($all);
		} catch (API_Exception $e) {
			\Civi::log()->error('Error with API$ Shortcode on post #' . get_the_ID(). ': ' . $e->getMessage());
			
			return '';
		}
	}
}
