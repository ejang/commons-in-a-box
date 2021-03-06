<?php
/**
 * Set up the settings page.
 *
 * @package Commons_In_A_Box
 * @subpackage Adminstration
 * @since 1.0-beta2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Setup the CBOX settings area.
 *
 * @since 1.0-beta2
 */
class CBox_Settings {

	/**
	 * Static variable to hold our various settings
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// setup globals
		$this->setup_globals();

		// setup our hooks
		$this->setup_hooks();
	}

	/**
	 * Setup globals.
	 */
	private function setup_globals() {
		$this->register_settings();
	}

	/**
	 * Setup our hooks.
	 */
	private function setup_hooks() {
		// setup the CBOX plugin menu
		add_action( 'cbox_admin_menu', array( $this, 'setup_settings_page' ), 20 );
	}

	/** SETTINGS-SPECIFIC *********************************************/

	/**
	 * Register settings.
	 *
	 * Used to render the checkboxes as well as the format to load these settings
	 * on the frontend.
	 *
	 * @see CBox_Admin_Settings::register_setting()
	 */
	private function register_settings() {
		// BuddyPress
		self::register_setting( array(
			'plugin_name'  => 'BuddyPress',
			'settings'     =>
				array(
					'label'       => __( 'Member Profile Default Tab', 'cbox' ),
					'description' => __( 'On a member page, set the default tab to "Profile" instead of "Activity".', 'cbox' ),
					'key'         => 'bp',                  // this is used to identify the plugin and as the filename suffix
					'class_name'  => 'CBox_BP_Profile_Tab', // this will load up the corresponding class; class must be created
				)
		) );

		// BuddyPress Group Email Subscription
		self::register_setting( array(
			'plugin_name'  => 'BuddyPress Group Email Subscription',
			'settings'     =>
				array(
					'label'       => __( 'All Mail', 'cbox' ),
					'description' => __( 'By default, when a member joins a group, email subscriptions are set to "No Mail".  Check this box to change the default subscription setting to "All Mail".', 'cbox' ),
					'key'         => 'ges',
					'class_name'  => 'CBox_GES_All_Mail'
				)
		) );
	}

	/**
	 * Register a plugin's settings in CBOX.
	 *
	 * Updates our private, static $settings variable in the process.
	 *
	 * @see CBox_Admin_Settings::register_settings()
	 */
	private function register_setting( $args = '' ) {
		$defaults = array(
			'plugin_name'       => false,   // (required) the name of the plugin as in the plugin header
			'settings'          => array(), // (required) multidimensional array
		);

		$r = wp_parse_args( $args, $defaults );

		if ( empty( $r['plugin_name'] ) || empty( $r['settings'] ) )
			return false;

		self::$settings[ $r['plugin_name'] ]['settings'] = $r['settings'];

	}

	/** ADMIN PAGE-SPECIFIC *******************************************/

	/**
	 * Setup CBOX's settings menu item.
	 */
	public function setup_settings_page() {
		// see if CBOX is fully setup
		if ( ! cbox_is_setup() )
			return;

		// add our settings page
		$page = add_submenu_page(
			'cbox',
			__( 'Commons In A Box Settings', 'cbox' ),
			__( 'Settings', 'cbox' ),
			'install_plugins', // todo - map cap?
			'cbox-settings',
			array( $this, 'admin_page' )
		);

		// load Plugin Dependencies plugin on the CBOX plugins page
		add_action( "load-{$page}", array( 'Plugin_Dependencies', 'init' ) );

		// validate any settings changes submitted from the CBOX settings page
		add_action( "load-{$page}", array( $this, 'validate_settings' ) );

		// inline CSS
		//add_action( "admin_head-{$page}", array( $this, 'inline_css' ) );
	}

	/**
	 * Validates settings submitted from the settings admin page.
	 */
	public function validate_settings() {
		if ( empty( $_REQUEST['cbox-settings-save'] ) )
			return;

		check_admin_referer( 'cbox_settings_options' );

		// get submitted values
		$submitted = (array) $_REQUEST['cbox_settings'];

		// update settings
		update_option( cbox()->settings_key, $submitted );

		// add an admin notice
		$prefix = is_network_admin() ? 'network_' : '';
		add_action( $prefix . 'admin_notices', create_function( '', "
			echo '<div class=\'updated\'><p><strong>' . __( 'Settings saved.', 'cbox' ) . '</strong></p></div>';
		" ) );
	}

	/**
	 * Renders the settings admin page.
	 */
	public function admin_page() {
	?>
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php _e( 'Commons In A Box Settings', 'cbox' ); ?></h2>

			<p><?php _e( 'CBOX can configure some important options for certain plugins.', 'cbox' ); ?>

			<form method="post" action="">
				<?php $this->render_options(); ?>

				<?php wp_nonce_field( 'cbox_settings_options' ); ?>

				<p><input type="submit" value="<?php _e( 'Save Changes', 'cbox' ); ?>" class="button-primary" name="cbox-settings-save" /></p>
			</form>
		</div>

	<?php
	}

	/**
	 * Renders all our checkboxes on the settings admin page.
	 */
	private function render_options() {
		// get all installed CBOX plugins
		$cbox_plugins = cbox()->plugins->get_plugins();

		// get all CBOX plugins by name
		$active = cbox()->plugins->organize_plugins_by_state( $cbox_plugins );

		// sanity check.  will probably never encounter this use-case.
		if ( empty( $active ) )
			return false;

		// get only active plugins and flip them for faster processing
		$active = array_flip( $active['deactivate'] );

		// get saved settings
		$cbox_settings = get_option( cbox()->settings_key );

		if ( empty( $settings ) )
			$settings = array();

		// parse and output settings
		foreach( self::$settings as $plugin => $settings ) {
			// if plugin doesn't exist, don't show the settings for that plugin
			if( ! isset( $active[$plugin] ) )
				continue;
		?>
			<h3><?php echo $plugin; ?></h3>

			<table class="form-table">
			<?php foreach ( $settings as $setting ) : ?>

				<tr valign="top">
					<th scope="row"><?php echo $setting['label']; ?></th>
					<td>
						<input id="<?php echo sanitize_title( $setting['label'] ); ?>" name="cbox_settings[<?php echo $setting['key'];?>][]" type="checkbox" value="<?php echo $setting['class_name']; ?>" <?php $this->is_checked( $setting['class_name'], $cbox_settings, $setting['key'] ); ?>  />
						<label for="<?php echo sanitize_title( $setting['label'] ); ?>"><?php echo $setting['description']; ?></label>
					</td>
				</tr>

			<?php endforeach; ?>
			</table>
		<?php
		}

	}

	/**
	 * Helper function to see if an option is checked.
	 */
	private function is_checked( $class_name, $settings, $key ) {
		if ( isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) && in_array( $class_name, $settings[$key] ) ) {
			echo 'checked="checked"';
		}
	}
}
