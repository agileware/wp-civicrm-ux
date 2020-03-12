<?php

class Civicrm_Ux_Shortcode_Civicrm_Listing extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return "ux_civicrm_listing";
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
		$mod_atts = shortcode_atts( [
			'dpid'   => 0,
			'limit'  => 0,
			'format' => 'default',
			'sort'   => '',
		], $atts, $tag );

		// get data processor information
		$dp = civicrm_api3( "DataProcessorOutput", 'get', [
			'sequential'        => 1,
			'type'              => "api",
			'data_processor_id' => $mod_atts['dpid']
		] );
		if ( ! $dp['count'] ) {
			return 'No data processor found';
		}
		$dp = array_shift( $dp['values'] );

		// the header
		$header = civicrm_api3( $dp['api_entity'], 'getfields', [
			'api_action' => "get",
		] );
		$header = $header['values'];

		// the main data
		$params = [
			'sequential' => 1,
			'options'    => [ 'limit' => $mod_atts['limit'], 'sort' => $mod_atts['sort'] ],
		];
		$result = civicrm_api3( $dp['api_entity'], $dp['api_action'], $params );

		return $this->render( $result, $header, $content, $mod_atts['format'] );
	}

	function render( $info, $header, $template = null, $format = 'default' ) {
		if ( count( $info ) == 0 ) {
			return "empty data";
		}

		switch ( $format ) {
			default:
				return $this->renderTable( $info['values'], $template, $header );
				break;
		}
	}

	function renderTable( $values, $template, $header ) {
		$header_html = "";
		$tbody_html  = "";
		foreach ( $header as $key => $value ) {
			if ( $value['api.return'] ) {
				$header_html .= "<th>{$value['title']}</th>";
			}
		}
		foreach ( $values as $record ) {
			$row_html = "";
			if ( $template ) {
				// use template
				$row_html = preg_replace_callback( '/\{\$([^{}]+)\}/m', function ( $matches ) use ( $record ) {
					return htmlentities( $record[ $matches[1] ] );
				}, $template );
			} else {
				// fixme deprecated - used on SPA listing
				foreach ( $record as $key => $value ) {
					if ( $key == 'website' ) {
						$row_html .= "<td><a href='" . htmlentities( $value ) . "'>" . htmlentities( $value ) . "</a></td>";
					} else {
						$row_html .= "<td>" . htmlentities( $value ) . "</td>";
					}
				}
			}

			$tbody_html .= "<tr>$row_html</tr>";
		}
		$html = "<table class='ux-cv-listing'><thead>$header_html</thead><tbody>$tbody_html</tbody></table>";

		return $html;
	}
}