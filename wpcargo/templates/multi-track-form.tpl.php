<?php if(!defined('ABSPATH')) { exit; } ?>
<?php
$shipment_numbers 	= wpcargo_can_track_multiple_shipments() ;
$result_page_id 	= (int)sanitize_text_field($atts['id']);
$get_action 		= !empty($result_page_id) ? get_page_link($result_page_id) : '';
?>

<div class="wpcargo-track wpcargo">
	<form method="post" name="wpcargo-track-form" action="<?php echo esc_html( $get_action ); ?>">
		<?php wp_nonce_field( 'wpcargo_track_shipment_action', 'track_shipment_nonce' ); ?>
		<table id="wpcargo-track-table" class="track_form_table">
			<tr class="track_form_tr">
				<th class="track_form_th" colspan="2"><h4><?php echo esc_html(apply_filters('wpcargo_tn_form_title', __('Enter tracking numbers at once separated by a comma.', 'wpcargo') )); ?></h4></th>
			</tr>
			<tr class="track_form_tr">
				<?php do_action('wpcargo_add_form_fields'); ?>
				<td class="track_form_td"><input class="input_track_num" type="text" name="<?php echo esc_attr(wpcargo_track_meta()); ?>" value="<?php echo esc_html( ( $shipment_numbers ) ? implode(',', $shipment_numbers ) : null ); ?>" autocomplete="off" placeholder="<?php echo esc_html(apply_filters('wpcargo_tn_placeholder', __('Enter Tracking Number', 'wpcargo' ) )); ?>" required></td>
				<td class="track_form_td submit-track"><input id="submit_wpcargo" class="wpcargo-btn wpcargo-btn-primary" name="wpcargo-submit" type="submit" value="<?php echo esc_html(apply_filters('wpcargo_tn_submit_val', __( 'Track Now', 'wpcargo' ) )); ?>"></td>
			</tr>
			<?php echo esc_html(apply_filters('wpcargo_example_text', ' <tr class="track_form_tr"><td class="track_form_td" colspan="2"><h4>'.__('Ex: 12345, 23456, 34567', 'wpcargo').'</h4></td></tr>')); ?>
		</table>
	</form>
</div>