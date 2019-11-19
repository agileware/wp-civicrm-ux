<?php

class Civicrm_Ux_Cf_Magic_Tag_Manager extends Abstract_Civicrm_Ux_Module_Manager {
    private static $form;

	/**
	 * The root directory of the manager instance php file.
	 * Should start from the root of plugin
	 *
	 * @return string
	 */
	public function get_directory() {
		return 'magic-tags';
	}

	/**
	 * The interface or class name to identify the instance
	 *
	 * @return string
	 */
	public function get_managed_interface() {
		return 'Abstract_Civicrm_Ux_Cf_Magic_Tag';
	}

	public function register_tags( $tags ) {
		$magic_tags = $tags['system']['tags'];

		foreach ( $this->instances as $instance ) {
			$magic_tags[] = $instance->get_tag_name();
		}

		$tags['system'] = [
			'type' => __( 'System Tags', 'caldera-form' ),
			'tags' => $magic_tags,
			'wrap' => array( '{', '}' )
		];

		return $tags;
	}

	/**
	 * @param $value
	 * @param $caller
	 *
	 * @return mixed
	 */
	public function dispatch_callback( $value, $caller ) {
		$tag = str_replace( [ '{', '}' ], '', $caller );

		foreach ( $this->instances as $instance ) {
			if ( $instance->get_tag_name() == $tag ) {
				return $instance->callback( $value );
			}
		}

		return $value;
	}

    /**
     * Set the Caldera form storage.
     */
    public static function set_form( $form ) {
        static::$form = $form;
    }

    /** Get the Caldera form,
     *
     */
    public static function form() {
        return static::$form;
    }
}
