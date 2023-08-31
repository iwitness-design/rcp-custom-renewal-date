<?php
/**
 * Plugin Name: Restrict Content Pro - Custom Renewal Date
 * Description: Allows for a specific renewal date to be assigned to annual membership levels. All members on that membership level will renew or expire on that date.
 * Author: Mission Lab
 * Author URI: https://missionlab.dev
 * Plugin URI: https://missionlab.dev/products/rcp-custom-renewal-date
 * Version: 1.0.0
 */

class RCP_Custom_Renewal_Date {

	/**
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function init() {

		$this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );

		$this->includes();

		add_filter( 'rcp_calculate_membership_level_expiration', array( $this, 'calculate_membership_level_expiration' ), 10, 2 );
	}

	/**
	 * Load our additional files
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function includes() {

		require_once $this->dir . 'includes/admin/level-edit.php';
		require_once $this->dir . 'includes/scripts.php';

		new RCP_Custom_Renewal_Date\Admin\Level_Edit();
		new RCP_Custom_Renewal_Date\Scripts();

	}

	/**
	 * Maybe override the calculated expiration date with the custom renewal date.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $expiration       Calculated date in MySQL format, or `none` if no expiration.
	 * @param \RCP\Membership_Level $membership_level Membership level object.
	 *
	 * @return string
	 * @see   rcp_calculate_subscription_expiration() for the original function.
	 *
	 */
	public function calculate_membership_level_expiration( $expiration, $membership_level ) {

		// don't override the expiration date if the membership level is lifetime
		if ( $membership_level->is_lifetime() ) {
			return $expiration;
		}

		// don't override the expiration date if the membership level has a trial
		if ( $membership_level->has_trial() ) {
			return $expiration;
		}

		// only override the expiration date if the membership level has a custom renewal date
		if ( 'year' != $membership_level->get_duration_unit() || empty( $membership_level->get_duration() ) ) {
			return $expiration;
		}

		$expiration_timestamp = $this->get_subscription_renewal_date( $membership_level->id, $membership_level->get_duration(), strtotime( $expiration ) );

		$expiration_date = date( 'Y-m-d H:i:s', $expiration_timestamp );

		return apply_filters( 'rcp_crd_calculate_membership_level_expiration', $expiration_date, $expiration, $membership_level );
	}

	/**
	 * Retrieve renewal date for membership level
	 *
	 * @param int $level_id ID of the membership level.
	 *
	 * @access  public
	 * @since   1.0
	 *
	 * @return string
	 */
	public function get_subscription_renewal_date( $level_id = 0, $duration = 1, $default = false  ) {
		/**
		 * @var RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		// convert date to current year
		if ( $date = $rcp_levels_db->get_meta( $level_id, 'custom_renewal_date', true ) ) {
			$year = date( 'Y', current_time( 'timestamp' ) );
			$month_day = date( 'm-d', $date );
			$date = strtotime( $year . '-' . $month_day . ' 23:59:59' );

			if ( $date < current_time( 'timestamp' ) ) {
				$year ++;
				$date = strtotime( $year . '-' . $month_day . ' 23:59:59' );
			}

			// allow for multiple years durations
			if ( $duration > 1 ) {
				$year += $duration - 1;
				$date = strtotime( $year . '-' . $month_day . ' 23:59:59' );
			}
		} else {
			$date = $default;
		}

		return apply_filters( 'rcp_crd_get_subscription_renewal_date', $date, $level_id );
	}

	/**
	 * Retrieve duration type for a membership level
	 *
	 * @param int $level_id ID of the level to get the duration type.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return string
	 */
	public function get_subscription_duration_type( $level_id = 0 ) {
		/**
		 * @var RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		return apply_filters( 'rcp_crd_get_subscription_duration_type', $rcp_levels_db->get_meta( $level_id, 'renewal_duration_type', true ), $level_id );
	}

}

/**
 * Load the plugin
 *
 * @access  public
 * @since   1.0.0
 */
function RCP_Custom_Renewal_Date_load() {
	global $rcp_custom_renewal_date;

	if( ! function_exists( 'rcp_is_active' ) ) {
		return;
	}

	$rcp_custom_renewal_date = new RCP_Custom_Renewal_Date;
}
add_action( 'plugins_loaded', 'RCP_Custom_Renewal_Date_load' );
