<?php

namespace RCP_Custom_Renewal_Date;

class Scripts {

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
		add_action( 'admin_head', array( $this, 'admin_scripts' ) );
//		add_action( 'wp_footer', array( $this, 'frontend_scripts' ) );
//		add_action( 'wp_ajax_get_duration_type', array( $this, 'get_duration_type' ) );
//		add_action( 'wp_ajax_nopriv_get_duration_type', array( $this, 'get_duration_type' ) );
	}

	/**
	 * Output our admin JS
	 *
	 * @access  public
	 * @since   1.0.0
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

					// handle logic for field visibility
					function fieldVisibility() {
						var renewal_type = $('#rcp-level-renewal-type').val();

						if ( 'year' !== $('#rcp-duration-unit').val() || '0' === $('#rcp-duration').val() || '0' !== $('#trial_duration').val() ) {
							$('#rcp-expiration-date-row').hide();
							$('#rcp-renewal-type-row').hide();
						} else {
							$('#rcp-renewal-type-row').show();

							if ('custom' === renewal_type) {
								$('#rcp-expiration-date-row').show();
							} else {
								$('#rcp-expiration-date-row').hide();
							}
						}
					}

					// run initially on page load
					fieldVisibility();

					// run on change
					$('#rcp-duration-unit, #rcp-duration, #rcp-level-renewal-type, #trial_duration').on('change', fieldVisibility);

					// show the correct number of days in the renewal day dropdown
					$('#rcp-level-renewal-month').on('change', function() {
						let days = $(this).find('option:selected').data('days');
						let options = '';

						for (let i = 1; i <= days; i++) {
							options += `<option value="${i}">${i}</option>`;
						}

						$('#rcp-level-renewal-day').html(options);
					})

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
							custom = $("#rcp_registration_form :input[value="+value+"]").attr('data-duration-type', 'custom'); // add a data attribute while we're here
							$(custom).siblings('label').find('.rcp_level_duration, .rcp_price .rcp_separator').remove();
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
	 * @since 1.0.0
	 *
	 * @return null - Sends a json array of subscription IDs with hard-set duration type.
	 */
	public function get_duration_type() {

		if ( empty( $_POST['ids']) ) {
			return;
		}

		/**
		 * @var \RCP_Custom_Renewal_Date $rcp_custom_renewal_date
		 */
		global $rcp_custom_renewal_date;

		$ids = array();
		foreach ( $_POST['ids'] as $id ) {

			$date = $rcp_custom_renewal_date->get_subscription_renewal_date( $id );
			$type = $rcp_custom_renewal_date->get_subscription_duration_type( $id );

			if ( 'custom' === $type || ! empty( $date ) ) {
				array_push( $ids, $id );
			}
		}

		wp_send_json( array(
			'ids' => $ids
		) );
	}
}
new Scripts;