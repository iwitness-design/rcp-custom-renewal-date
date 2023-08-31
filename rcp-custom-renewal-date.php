<?php
/**
 * Plugin Name: Restrict Content Pro - Custom Renewal Date
 * Description: Allows for a specific renewal date to be assigned to membership levels. All members on that membership level will renew or expire on that date.
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

//		add_filter( 'rcp_membership_get_expiration_date', array( $this, 'get_membership_expiration_date' ), 10, 4 );
//		add_filter( 'rcp_membership_get_expiration_time', array( $this, 'get_membership_renewal_time' ), 10, 3 );
		add_filter( 'rcp_calculate_membership_level_expiration', array( $this, 'calculate_membership_level_expiration' ), 10, 3 );
		add_filter( 'rcp_membership_calculated_expiration_date', array( $this, 'calculate_membership_expiration' ), 10, 3 );

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
	 * Maybe modify the membership expiration date, if the associated membership level has a custom renewal date.
	 *
	 * @param string         $expiration    Membership expiration date.
	 * @param bool           $formatted     Whether or not the date should be nicely formatted.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_membership_expiration_date( $expiration, $formatted, $membership_id, $membership ) {

		$level_id = $membership->get_object_id();

		if( ! empty( $level_id ) ) {

			$date = $this->get_subscription_renewal_date( $level_id );

			if( ! empty( $date ) ) {

				$expiration = $date;

				if ( $formatted && 'none' != $expiration ) {
					$expiration = date_i18n( get_option( 'date_format' ), strtotime( $expiration, current_time( 'timestamp' ) ) );
				}

			}
		}

		return $expiration;

	}

	/**
	 * Maybe modify the membership expiration timestamp, if the associated membership level has a custom renewal date.
	 *
	 * @param int|false      $timestamp     Membership expiration timestamp.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 1.0.0
	 * @return int|false
	 */
	public function get_membership_renewal_time( $timestamp, $membership_id, $membership ) {

		$level_id = $membership->get_object_id();

		if( ! empty( $level_id ) ) {

			$date = $this->get_subscription_renewal_date( $level_id );

			if( ! empty( $date ) ) {

				$timestamp = strtotime( $date );

			}

		}

		return $timestamp;

	}

	/**
	 * Maybe override the calculated expiration date with the custom renewal date.
	 *
	 * @since 1.0.0
	 *
	 * @param \RCP\Membership_Level $membership_level Membership level object.
	 * @param bool                  $set_trial        Whether or not to set a trial.
	 * @param string                $expiration       Calculated date in MySQL format, or `none` if no expiration.
	 *
	 * @return string
	 * @see   rcp_calculate_subscription_expiration() for the original function.
	 *
	 */
	public function calculate_membership_level_expiration( $expiration, $membership_level, $set_trial ) {

		// don't override the expiration date if the membership level is lifetime or doesn't have a custom renewal date
		if ( empty( $date ) || $membership_level->is_lifetime() ) {
			return $expiration;
		}

		// don't override the expiration date if the membership level has a trial
		if ( $set_trial && $membership_level->has_trial() ) {
			return $expiration;
		}

		if ( 'year' != $membership_level->get_duration_unit() || empty( $membership_level->get_duration() ) ) {
			return $expiration;
		}

		$expiration_timestamp = $this->get_subscription_renewal_date( $membership_level->id, $membership_level->get_duration(), strtotime( $expiration ) );

		$expiration_date = date( 'Y-m-d H:i:s', $expiration_timestamp );

		return apply_filters( 'rcp_crd_calculate_membership_level_expiration', $expiration_date, $expiration, $membership_level, $set_trial );
	}

	/**
	 * Maybe override the expiration date calculated for membership renewals.
	 *
	 * This actually will probably never be used, it's just here in case someone tries to renew a membership that has
	 * a hard-set expiration date, which should never actually happen.
	 *
	 * @param string         $expiration    Calculated expiration date.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function calculate_membership_expiration( $expiration, $membership_id, $membership ) {

		$membership_level_id = $membership->get_object_id();
		$membership_level    = rcp_get_membership_level( $membership_level_id );

		// don't override the expiration date if the membership level is lifetime or is trialing
		if ( $membership->is_trialing() || $membership_level->is_lifetime() ) {
			return $expiration;
		}

		if ( 'year' != $membership_level->get_duration_unit() || empty( $membership_level->get_duration() ) ) {
			return $expiration;
		}

		$expiration_timestamp = $this->get_subscription_renewal_date( $membership_level_id, $membership_level->get_duration(), strtotime( $expiration ) );

		$expiration_date = date( 'Y-m-d H:i:s', $expiration_timestamp );

		return apply_filters( 'rcp_crd_calculate_membership_level_expiration', $expiration_date, $expiration, $membership );
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
