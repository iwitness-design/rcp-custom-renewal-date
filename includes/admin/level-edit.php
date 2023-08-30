<?php

class RCP_HSED_Level_Edit {

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

		add_action( 'rcp_add_subscription_form', array( $this, 'date_field' ) );
		add_action( 'rcp_edit_subscription_form', array( $this, 'date_field' ) );
		add_action( 'rcp_add_subscription', array( $this, 'store_expiration_date' ), 10, 2 );
		add_action( 'rcp_edit_subscription_level', array( $this, 'store_expiration_date' ), 10, 2 );
		add_action( 'rcp_remove_level', array( $this, 'remove_hardset_level_options' ), 10, 1 );
	}

	/**
	 * Output the date field
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function date_field( $level ) {

		/**
		 * @var RCP_Hardset_Expiration_Dates $rcp_hsed
		 */
		global $rcp_hsed;

		$date = is_object( $level ) ? $rcp_hsed->get_subscription_expiration_date( $level->id ) : '';
		$type = is_object( $level ) ? $rcp_hsed->get_subscription_duration_type( $level->id ) : 'standard';
		$display = ( 'standard' === $type || empty( $type ) ) ? 'style="display:none"' : '';
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="rcp-level-duration-type"><?php _e( 'Duration Type', 'rcp-hsed' ); ?></label>
			</th>
			<td>
				<select id="rcp-level-duration-type" name="rcp-level-duration-type">
					<option value="standard" <?php selected( $type, 'standard' ); ?>><?php esc_html_e( 'Standard' ); ?></option>
					<option value="hardset" <?php selected( $type, 'hardset' ); ?>><?php esc_html_e( 'Specific date' ); ?></option>
				</select>
				<p class="description"><?php _e( 'Select the duration type for this level. Select "Standard" for a regular subscription. Select "Specific date" to define a hard-set expiration date.', 'rcp-hsed' ); ?></p>
			</td>
		</tr>

		<tr id="rcp-expiration-date-row" class="form-field" <?php echo $display; ?>>
			<th scope="row" valign="top">
				<label for="rcp-expiration-date"><?php _e( 'Expiration Date', 'rcp-hsed' ); ?></label>
			</th>
			<td>
				<input name="rcp_level_expiration" id="rcp-expiration-date" class="rcp-datepicker" value="<?php echo esc_attr( $date ); ?>"/>
				<p class="description"><?php _e( 'Select the expiration date for this subscription level.', 'rcp-hsed' ); ?></p>
			</td>
		</tr>
<?php
	}

	/**
	 * Store the date for the subscription level
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function store_expiration_date( $level_id = 0, $args ) {

		if ( empty( $_POST['rcp_edit_level_nonce'] ) && empty( $_POST['rcp_add_level_nonce'] ) ) {
			return;
		}

		/**
		 * @var RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		if ( ! empty( $_POST['rcp-level-duration-type'] ) && 'hardset' === $_POST['rcp-level-duration-type'] ) {

			$rcp_levels_db->update_meta( $level_id, 'hardset_duration_type', 'hardset' );

		} else {

			$rcp_levels_db->delete_meta( $level_id, 'hardset_duration_type' );


		}

		if ( ! empty( $_POST['rcp_level_expiration'] ) ) {

			$rcp_levels_db->update_meta( $level_id, 'hardset_expiration_date', sanitize_text_field( $_POST['rcp_level_expiration'] ) );

		} else {

			$rcp_levels_db->delete_meta( $level_id, 'hardset_expiration_date' );

		}
	}

	/**
	 * Removes the saved options for a subscription level when it is deleted.
	 *
	 * @access public
	 * @since 1.0.1
	 */
	public function remove_hardset_level_options( $level_id = 0 ) {

		if ( empty( $level_id ) ) {
			return;
		}

		/**
		 * @var RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

		$rcp_levels_db->delete_meta( $level_id, 'hardset_duration_type' );
		$rcp_levels_db->delete_meta( $level_id, 'hardset_expiration_date' );

		delete_option( 'rcp_level_duration_type_' . $level_id );
		delete_option( 'rcp_level_expiration_' . $level_id );
	}

}
new RCP_HSED_Level_Edit;