<?php

abstract class Abstract_Civicrm_Ux_Cf_Magic_Tag implements iCivicrm_Ux_Managed_Instance {
	protected $manager;

	/**
	 * @param Civicrm_Ux_Cf_Magic_Tag_Manager $manager
	 */
	public function __construct( $manager ) {
		$this->manager = $manager;
	}

	/**
	 * The tag name
	 *
	 * @return string
	 */
	abstract function get_tag_name();

	/**
	 * The callback function. Should return $value if no changes.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	abstract function callback( $value );
}