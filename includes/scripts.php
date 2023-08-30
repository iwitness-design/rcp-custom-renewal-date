<?php

class RCP_HSED_Scripts {

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
		add_action( 'admin_head', array( $this, 'admin_scripts' ) );
		add_action( 'wp_footer', array( $this, 'frontend_scripts' ) );
		add_action( 'wp_ajax_get_duration_type', array( $this, 'get_duration_type' ) );
		add_action( 'wp_ajax_nopriv_get_duration_type', array( $this, 'get_duration_type' ) );
	}

	/**
	 * Output our admin JS
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function admin_scripts() {
		global $rcp_subscriptions_page;
		if ( $rcp_subscriptions_page !== get_current_screen()->id ) {
			return;
		}
?>
		<script type="text/javascript">
			(function($) {
				$(document).ready( function() {

					var is_hardset = false;

					$('.rcp-sub-duration-col').remove();

					if ( $('#rcp-level-duration-type').val() === 'hardset' ) {
						is_hardset = true;
						$('#rcp-duration, #rcp-duration-unit').prop('disabled', true);

						// We need to add a hidden input field with the value "0" because disabled fields do not get sent through $_POST.
						$('#rcp-duration').attr( 'value', '0' ).parent().append('<input type="hidden" id="rcp-hardset-expiration-duration" name="duration" value="0">');
					}

					$('#rcp-level-duration-type').on('change', function() {
						if ( $(this).val() === 'hardset' ) {
							$('#rcp-duration, #rcp-duration-unit').prop('disabled', true);
							$('#rcp-duration').attr( 'value', '0' ).parent().append('<input type="hidden" id="rcp-hardset-expiration-duration" name="duration" value="0">');
							$('#rcp-expiration-date-row').fadeIn();
							is_hardset = true;
						} else {
							$('#rcp-expiration-date-row').fadeOut();
							$('#rcp-duration, #rcp-duration-unit').prop('disabled', false);
							$('#rcp-hardset-expiration-duration').remove();
							is_hardset = false;
						}
					});
				});
			})(jQuery);

		</script>
<?php
	}

	/**
	 * Output our frontend JS
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function frontend_scripts() {

		if( ! rcp_is_registration_page() ) {
			return;
		}
?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {

				var level_ids = [];

				$.each($('#rcp_subscription_levels .rcp_level:radio'), function(key, value) {
					level_id = $(value).val();
					level_ids.push(level_id);
				});

				$.ajax({
					data: {
						action: 'get_duration_type',
						ids: level_ids
					},
					type: "POST",
					dataType: "json",
					url: rcp_script_options.ajaxurl,
					success: function(response) {
						$.each(response.ids, function(key, value) {
							hardset = $("#rcp_registration_form :input[value="+value+"]").attr('data-duration-type', 'hardset'); // add a data attribute while we're here
							$(hardset).siblings('label').find('.rcp_level_duration, .rcp_price .rcp_separator').remove();
						});
					},
					error: function(response) {
						console.log(response);
					}
				});
			});
		</script>
<?php
	}

	/**
	 * Gets the duration type of the requested subscription levels.
	 *
	 * @access public
	 * @since 1.0.1
	 *
	 * @return array An array of subscription IDs with hard-set duration type.
	 */
	public function get_duration_type() {

		if ( empty( $_POST['ids']) ) {
			return;
		}

		/**
		 * @var RCP_Hardset_Expiration_Dates $rcp_hsed
		 */
		global $rcp_hsed;

		$ids = array();
		foreach ( $_POST['ids'] as $id ) {

			$date = $rcp_hsed->get_subscription_expiration_date( $id );
			$type = $rcp_hsed->get_subscription_duration_type( $id );

			if ( 'hardset' === $type || ! empty( $date ) ) {
				array_push( $ids, $id );
			}
		}

		wp_send_json( array(
			'ids' => $ids
		) );
	}
}
new RCP_HSED_Scripts;