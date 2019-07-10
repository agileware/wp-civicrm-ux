<?php

interface iCivicrm_Ux_Managed_Instance {
	/**
	 * iCivicrm_Ux_Managed_Instance constructor.
	 *
	 * @param Abstract_Civicrm_Ux_Module_manager $manager
	 */
	public function __construct($manager);
}