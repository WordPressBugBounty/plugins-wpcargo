<?php if(!defined('ABSPATH')) { exit; } ?>
<?php
$shipment_id	= $shipment_detail->ID;
$tracknumber	= $shipment_detail->post_title;
?>
<div id="wpcargo-print-layout" style="overflow: hidden;">
	<div class="print-tn one-half first">
		<?php echo wp_kses($wpcargo->barcode($shipment_id, true), array(
			'img' => array(
				'class' => array(),
				'src' => array(),
				'alt' => array()
			)
		)); ?>
		<span style="display: block; font-size: 18px!important;"><?php echo esc_html( $tracknumber ); ?></span>
	</div>
	<div class="print-logo one-half">
		<?php $options = get_option('wpcargo_option_settings');  ?>
		<img src="<?php echo esc_html($options['settings_shipment_ship_logo']); ?>">
	</div>
</div>