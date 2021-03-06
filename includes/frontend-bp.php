<?php
/**
 * BuddyPress Mods
 *
 * If BuddyPress settings are toggled under the CBOX admin settings page,
 * setup the code for each setting here.
 *
 * @package Commons_In_A_Box
 * @subpackage Frontend
 * @since 1.0-beta2
 */

// setup globals for BuddyPress
cbox()->frontend->bp = new stdClass;
cbox()->frontend->bp->is_setup = function_exists( 'bp_include' );

/**
 * Changes the default tab on a BP member page from 'Activity' to 'Profile'
 *
 * @since 1.0-beta2
 */
class CBox_BP_Profile_Tab {
	public static function init() {
		new self();
	}

	public function __construct() {
		if ( ! defined( 'BP_DEFAULT_COMPONENT' ) )
			define( 'BP_DEFAULT_COMPONENT', 'profile' );
	}
}
