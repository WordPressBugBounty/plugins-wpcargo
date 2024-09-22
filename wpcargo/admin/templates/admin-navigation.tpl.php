<?php
	$view = sanitize_text_field($_GET['page']);
?>
<h2 id="wpcargo-settings-nav" class="nav-tab-wrapper">
  <a class="nav-tab <?php echo ( $view == 'wpcargo-settings') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpcargo-settings'; ?>" ><?php echo esc_html( wpcargo_general_settings_label() ); ?></a>
  <?php do_action('wpc_add_settings_nav'); ?>
</h2>