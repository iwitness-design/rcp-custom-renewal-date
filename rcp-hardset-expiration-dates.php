<?php
/**
 * Plugin Name: Restrict Content Pro - Hard-set Expiration Dates
 * Description: Allows for a specific expiration date to be assigned to membership levels. All members will expire on that date.
 * Author: Sandhills Development, LLC
 * Author URL: https://sandhillsdev.com
 * Plugin URL: http://restrictcontentpro.com/addons/hardset-expiration-dates
 * Version: 1.1.4
 * iThemes Package: rcp-hardset-expiration-dates
 */

class RCP_Hardset_Expiration_Dates {

	/**
	 * @access  public
	 * @since   1.0
	 */
	public $dir;

	/**
	 * @access  public
	 * @since   1.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function init() {

		$this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );

		if( class_exists( 'RCP_Add_On_Updater' ) ) {
			$updater = new RCP_Add_On_Updater( 285, __FILE__, '1.1.4' );
		}

		$this->includes();

		add_filter( 'rcp_registration_is_recurring', array( $this, 'maybe_disable_auto_renew' ) );
		add_filter( 'rcp_get_levels', array( $this, 'maybe_deactivate_subscription_level' ), 9 );
		add_filter( 'rcp_show_subscription_level', array( $this, 'hide_expired_hardset_levels' ), 10, 3 );

		if ( version_compare( RCP_PLUGIN_VERSION, '3.0', '>=' ) ) {
			add_filter( 'rcp_membership_get_expiration_date', array( $this, 'get_membership_expiration_date' ), 10, 4 );
			add_filter( 'rcp_membership_get_expiration_time', array( $this, 'get_membership_expiration_time' ), 10, 3 );
			add_filter( 'rcp_calculate_membership_level_expiration', array( $this, 'calculate_membership_level_expiration' ), 10, 3 );
			add_filter( 'rcp_membership_calculated_expiration_date', array( $this, 'calculate_membership_expiration' ), 10, 3 );
			add_filter( 'rcp_membership_can_renew', array( $this, 'maybe_disable_renewals' ), 10, 3 );
		} else {
			add_filter( 'rcp_member_get_expiration_date', array( $this, 'get_member_expiration_date' ), 10, 5 );
			add_filter( 'rcp_member_get_expiration_time', array( $this, 'get_member_expiration_time' ), 10, 3 );
			add_filter( 'rcp_member_calculated_expiration', array( $this, 'calculate_expiration' ), 10, 3 );
		}

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

	}

	/**
	 * Retrieve member's expiration date from membership level
	 *
	 * @deprecated 1.1.1 In favour of get_membership_expiration_date()
	 * @see RCP_Hardset_Expiration_Dates::get_membership_expiration_date()
	 *
	 * @param string     $expiration Expiration date.
	 * @param int        $member_id  ID of the user.
	 * @param RCP_Member $member     Member object.
	 * @param bool       $formatted  Whether or not the final value should be formatted.
	 * @param bool       $pending    Whether or not to check the pending expiration date.
	 *
	 * @access  public
	 * @since   1.0
	 * @return string
	 */
	public function get_member_expiration_date( $expiration = '', $member_id = 0, RCP_Member $member, $formatted = true, $pending = true ) {

		$level_id = $member->get_subscription_id();

		if( ! empty( $level_id ) ) {

			$date = $this->get_subscription_expiration_date( $level_id );

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
	 * Retrieve member's expiration time from membership level
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_member_expiration_time( $expiration = '', $member_id = 0, RCP_Member $member ) {

		$level_id = $member->get_subscription_id();

		if( ! empty( $level_id ) ) {

			$date = $this->get_subscription_expiration_date( $level_id );

			if( ! empty( $date ) ) {

				$expiration = strtotime( $date );

			}

		}

		return $expiration;

	}

	/**
	 * Maybe modify the membership expiration date, if the associated membership level has a hard-set date.
	 *
	 * @param string         $expiration    Membership expiration date.
	 * @param bool           $formatted     Whether or not the date should be nicely formatted.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 1.1.1
	 * @return string
	 */
	public function get_membership_expiration_date( $expiration, $formatted, $membership_id, $membership ) {

		$level_id = $membership->get_object_id();

		if( ! empty( $level_id ) ) {

			$date = $this->get_subscription_expiration_date( $level_id );

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
	 * Maybe modify the membership expiration timestamp, if the associated membership level has a hard-set date.
	 *
	 * @param int|false      $timestamp     Membership expiration timestamp.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 1.1.1
	 * @return int|false
	 */
	public function get_membership_expiration_time( $timestamp, $membership_id, $membership ) {

		$level_id = $membership->get_object_id();

		if( ! empty( $level_id ) ) {

			$date = $this->get_subscription_expiration_date( $level_id );

			if( ! empty( $date ) ) {

				$timestamp = strtotime( $date );

			}

		}

		return $timestamp;

	}

	/**
	 * Calculate next expiration date
	 *
	 * @deprecated 1.1.1 In favour of `calculate_membership_level_expiration()`
	 * @see RCP_Hardset_Expiration_Dates::calculate_membership_level_expiration()
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function calculate_expiration( $expiration = '', $member_id = 0, RCP_Member $member ) {

		$level_id = get_user_meta( $member_id, 'rcp_pending_subscription_level', true );

		if( empty( $level_id ) ) {

			$level_id = $member->get_subscription_id();

		}

		if( ! empty( $level_id ) ) {

			$date = $this->get_subscription_expiration_date( $level_id );

			if( ! empty( $date ) ) {

				$expiration = $date;

			}

		}

		return $expiration;

	}

	/**
	 * Maybe override the calculated expiration date with the hard-set date.
	 *
	 * @param string $expiration       Calculated date in MySQL format, or `none` if no expiration.
	 * @param object $membership_level Membership level object.
	 * @param bool   $set_trial        Whether or not to set a trial.
	 *
	 * @since 1.1.1
	 * @return string
	 */
	public function calculate_membership_level_expiration( $expiration, $membership_level, $set_trial ) {

		$date = $this->get_subscription_expiration_date( $membership_level->id );

		if( ! empty( $date ) ) {
			$expiration = $date;
		}

		return $expiration;

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
	 * @since 1.1.1
	 * @return string
	 */
	public function calculate_membership_expiration( $expiration, $membership_id, $membership ) {

		$date = $this->get_subscription_expiration_date( $membership->get_object_id() );

		if( ! empty( $date ) ) {
			$expiration = $date;
		}

		return $expiration;

	}

	/**
	 * Retrieve expiration date for membership level
	 *
	 * @param int $level_id ID of the membership level.
	 *
	 * @access  public
	 * @since   1.0
	 *
	 * @return string
	 */
	public function get_subscription_expiration_date( $level_id = 0 ) {

		// First check if the setting is in the membership level meta table, and if so return that.

		/**
		 * @var RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		$expiration_date = $rcp_levels_db->get_meta( $level_id, 'hardset_expiration_date', true );

		if ( ! empty( $expiration_date ) ) {
			return $expiration_date;
		}

		// If not, look in the options table. If it's still set there, we need to move it to the meta table.

		$expiration_date = get_option( 'rcp_level_expiration_' . $level_id, '' );

		if ( ! empty( $expiration_date ) ) {
			$result = $rcp_levels_db->update_meta( $level_id, 'hardset_expiration_date', sanitize_text_field( $expiration_date ) );

			if ( $result ) {
				delete_option( 'rcp_level_expiration_' . $level_id );
			}
		}

		return $expiration_date;
	}

	/**
	 * Retrieve duration type for a membership level
	 *
	 * @param int $level_id ID of the level to get the duration type.
	 *
	 * @access public
	 * @since 1.1
	 * @return string
	 */
	public function get_subscription_duration_type( $level_id = 0 ) {

		// First check if the setting is in the membership level meta table, and if so return that.

		/**
		 * @var RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		$duration_type = $rcp_levels_db->get_meta( $level_id, 'hardset_duration_type', true );

		if ( ! empty( $duration_type ) ) {
			return $duration_type;
		}

		// If not, look in the options table. If it's still set there, we need to move it to the meta table.

		$duration_type = get_option( 'rcp_level_duration_type_' . $level_id );

		if ( ! empty( $duration_type ) ) {
			$result = $rcp_levels_db->update_meta( $level_id, 'hardset_duration_type', sanitize_text_field( $duration_type ) );

			if ( $result ) {
				delete_option( 'rcp_level_duration_type_' . $level_id );
			}
		}

		return $duration_type;

	}

	/**
	 * Retrieve expiration date for membership level
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function maybe_disable_auto_renew( $auto_renew ) {

		$exp = $this->get_subscription_expiration_date( rcp_get_registration()->get_subscription() );

		if( ! empty( $exp ) ) {
			$auto_renew = false;
		}

		return $auto_renew;
	}

	/**
	 * Hides and deactivates the membership level if its
	 * expiration date has passed.
	 *
	 * @access public
	 * @since 1.0.4
	 */
	public function maybe_deactivate_subscription_level( $levels ) {

		if ( ! rcp_is_registration_page() ) {
			return $levels;
		}

		foreach( $levels as $key => $level ) {

			if ( ! $expiration = $this->get_subscription_expiration_date( $level->id ) ) {
				continue;
			}

			if ( strtotime( 'now' ) > strtotime( $expiration ) ) {
				unset( $levels[$key] );
				global $rcp_levels_db;
				$rcp_levels_db->update( $level->id, array( 'status' => 'inactive' ) );
			}
		}

		return $levels;
	}

	/**
	 * Prevent expired membership levels from appearing on the registration form.
	 * This is a fallback in case `maybe_deactivate_subscription_level()` doesn't trigger for whatever reason.
	 *
	 * @param bool $show_level Whether or not the level should be displayed on the registration form.
	 * @param int  $level_id   ID of the level being checked.
	 * @param int  $user_id    ID of the user registering.
	 *
	 * @since 1.1.2
	 * @return bool
	 */
	public function hide_expired_hardset_levels( $show_level, $level_id, $user_id ) {

		if ( ! $show_level ) {
			return;
		}

		if ( ! $expiration = $this->get_subscription_expiration_date( $level_id ) ) {
			return $show_level;
		}

		if ( current_time( 'timestamp' ) > strtotime( $expiration, current_time( 'timestamp' ) ) ) {
			$show_level = false;
		}

		return $show_level;
	}

	/**
	 * Disable renewals on memberships with a hard-set expiration date.
	 *
	 * @param bool           $can_renew     Whether or not the membership can be renewed.
	 * @param int            $membership_id ID of the membership being checked.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 1.1.2
	 * @return bool
	 */
	public function maybe_disable_renewals( $can_renew, $membership_id, $membership ) {

		// If this membership has never been activated, then don't impose any restrictions.
		if ( method_exists( $membership, 'get_activated_date' ) && ! $membership->get_activated_date() ) {
			return $can_renew;
		}

		if ( $this->get_subscription_expiration_date( $membership->get_object_id() ) ) {
			$can_renew = false;
		}

		return $can_renew;

	}
}

/**
 * Load the plugin
 *
 * @access  public
 * @since   1.0
 */
function rcp_hardset_expiration_dates_load() {
	global $rcp_hsed;

	if( ! function_exists( 'rcp_is_active' ) ) {
		return;
	}

	$rcp_hsed = new RCP_Hardset_Expiration_Dates;
}
add_action( 'plugins_loaded', 'rcp_hardset_expiration_dates_load' );


if ( ! function_exists( 'ithemes_repository_name_updater_register' ) ) {
	function ithemes_repository_name_updater_register( $updater ) {
		$updater->register( 'rcp-hardset-expiration-dates', __FILE__ );
	}
	add_action( 'ithemes_updater_register', 'ithemes_repository_name_updater_register' );

	require( __DIR__ . '/lib/updater/load.php' );
}
