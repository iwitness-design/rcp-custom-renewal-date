<?php

namespace RCP_Custom_Renewal_Date\Admin;

class Level_Edit {

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
	 * @since   1.0.0
	*/
	public function init() {
		add_action( 'rcp_add_subscription_form', array( $this, 'date_field' ) );
		add_action( 'rcp_edit_subscription_form', array( $this, 'date_field' ) );
		add_action( 'rcp_add_subscription', array( $this, 'store_expiration_date' ) );
		add_action( 'rcp_edit_subscription_level', array( $this, 'store_expiration_date' ) );
		add_action( 'rcp_remove_level', array( $this, 'remove_level_options' ), 10, 1 );
	}

	/**
	 * Output the date field
	 *
	 * @access  public
	 * @since   1.0.0
	*/
	public function date_field( $level ) {

		/**
		 * @var \RCP_Custom_Renewal_Date $rcp_custom_renewal_date
		 */
		global $rcp_custom_renewal_date;

		$date = is_object( $level ) ? $rcp_custom_renewal_date->get_subscription_renewal_date( $level->id ) : '';
		$type = is_object( $level ) ? $rcp_custom_renewal_date->get_subscription_duration_type( $level->id ) : 'standard';
		$display = ( 'standard' === $type || empty( $type ) ) ? 'style="display:none"' : '';

		if ( ! $date ) {
			$date = strtotime( 'Jan 1' );
		}
		?>
		<tr id="rcp-renewal-type-row" class="form-field">
			<th scope="row" valign="top">
				<label for="rcp-level-renewal-type"><?php _e( 'Duration Type', 'rcp-custom-renewal-date' ); ?></label>
			</th>
			<td>
				<select id="rcp-level-renewal-type" name="rcp-level-renewal-type">
					<option value="standard" <?php selected( $type, 'standard' ); ?>><?php esc_html_e( 'Standard' ); ?></option>
					<option value="custom" <?php selected( $type, 'custom' ); ?>><?php esc_html_e( 'Custom' ); ?></option>
				</select>
				<p class="description"><?php _e( 'Select the duration type for this level. Select "Standard" for a regular subscription. Select "Custom" to define a renewal date.', 'rcp-custom-renewal-date' ); ?></p>
			</td>
		</tr>

		<tr id="rcp-expiration-date-row" class="form-field" <?php echo $display; ?>>
			<th scope="row" valign="top">
				<label for="rcp-expiration-date"><?php _e( 'Renewal Date', 'rcp-custom-renewal-date' ); ?></label>
			</th>
			<td>
				<select name="rcp-level-renewal-month" id="rcp-level-renewal-month" value="">
					<?php for( $m = 1; $m <= 12; $m++ ) : ?>
						<option value="<?php echo $m; ?>" data-days="<?php echo cal_days_in_month( CAL_GREGORIAN, $m, 2001 ); ?>" <?php selected( date( 'n', $date ), $m ); ?>><?php echo date( 'F', mktime( 0, 0, 0, $m, 1 ) ); ?></option>
					<?php endfor; ?>
				</select>

				<select name="rcp-level-renewal-day" id="rcp-level-renewal-day" value="">
					<?php for( $d = 1; $d <= 31; $d++ ) : ?>
						<option value="<?php echo $d; ?>" <?php selected( date( 'j', $date ), $d ); ?>><?php echo $d; ?></option>
					<?php endfor; ?>
				</select>

				<p class="description"><?php _e( 'Select the renewal date for this subscription level.', 'rcp-custom-renewal-date' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Store the date for the subscription level
	 *
	 * @access  public
	 * @since   1.0.0
	*/
	public function store_expiration_date( $level_id = 0 ) {

		if ( empty( $_POST['rcp_edit_level_nonce'] ) && empty( $_POST['rcp_add_level_nonce'] ) ) {
			return;
		}

		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		if ( empty( $_POST['duration'] ) || empty( $_POST['duration_unit'] ) || 'year' !== $_POST['duration_unit'] || ! empty( $_POST['trial_duration'] ) ) {
			$rcp_levels_db->delete_meta( $level_id, 'renewal_duration_type' );
			$rcp_levels_db->delete_meta( $level_id, 'custom_renewal_date' );

			return;
		}

		if ( ! empty( $_POST['rcp-level-renewal-type'] ) && 'custom' === $_POST['rcp-level-renewal-type'] ) {
			$rcp_levels_db->update_meta( $level_id, 'renewal_duration_type', 'custom' );
			$date = date( 'U', strtotime( $_POST['rcp-level-renewal-month'] . '/' . $_POST['rcp-level-renewal-day'] . '/' . date( 'Y' ) . ' 23:59:59' ) );

			if ( ! empty( $_POST['rcp-level-renewal-month'] ) && ! empty( $_POST['rcp-level-renewal-day']) ) {
				$rcp_levels_db->update_meta( $level_id, 'custom_renewal_date', $date );
			} else { // this shouldn't ever happen
				$rcp_levels_db->delete_meta( $level_id, 'custom_renewal_date' );
			}
		} else {
			$rcp_levels_db->delete_meta( $level_id, 'renewal_duration_type' );
			$rcp_levels_db->delete_meta( $level_id, 'custom_renewal_date' );
		}
	}

	/**
	 * Removes the saved options for a subscription level when it is deleted.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function remove_level_options( $level_id = 0 ) {

		if ( empty( $level_id ) ) {
			return;
		}

		/**
		 * @var \RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		$rcp_levels_db->delete_meta( $level_id, 'renewal_duration_type' );
		$rcp_levels_db->delete_meta( $level_id, 'custom_renewal_date' );

		delete_option( 'rcp_level_duration_type_' . $level_id );
		delete_option( 'rcp_level_expiration_' . $level_id );
	}

}