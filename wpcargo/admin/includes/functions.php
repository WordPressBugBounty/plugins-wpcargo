<?php
if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}
function is_wpcargo_client(){
	$current_user = wp_get_current_user();
	$roles 		  =  $current_user->roles;
	if( in_array( 'wpcargo_client', $roles ) ){
		return true;
	}
	return false;
}
function wpcargo_include_template( $file_name ){
    $file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPCARGO_PLUGIN_PATH.'templates/'.$file_name.'.php';
        $template_path  = apply_filters( "wpcargo_locate_template_{$file_slug}", $template_path );
    }
    return $template_path;
}
function wpcargo_admin_include_template( $file_name, $shipment ){
    $file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/admin/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPCARGO_PLUGIN_PATH.'admin/templates/'.$file_name.'.php';
        $template_path  = apply_filters( "wpcargo_locate_admin_template_{$file_slug}", $template_path );
    }
    include_once( $template_path ); 
}
function wpcargo_can_track_shipment( ) {
    $shipment_number = isset( $_REQUEST[wpcargo_track_meta()] ) ? sanitize_text_field( urldecode($_REQUEST[wpcargo_track_meta()]) ) : null;
    return apply_filters( 'wpcargo_can_track_shipment', $shipment_number );
}

function wpcargo_can_track_multiple_shipments( ) {

    $shipment_numbers = isset( $_REQUEST[wpcargo_track_meta()] ) ? sanitize_text_field( urldecode($_REQUEST[wpcargo_track_meta()]) ) : null;

    if ( $shipment_numbers == null ) {
        return null;
    }

    $shipment_numbers = explode( ',', $shipment_numbers );

    if ( count( $shipment_numbers ) > 0 ) {
        
        for ( $i = 0; $i < count( $shipment_numbers ); $i++ ) {
            $shipment_numbers[$i] = trim( $shipment_numbers[$i] );
        }

    } 

    return apply_filters( 'wpcargo_can_track_multiple_shipments', $shipment_numbers );

}

function wpcargo_track_meta( ) {
    return apply_filters( 'wpcargo_track_meta', 'wpcargo_tracking_number' );
}

function wpcargo_trackform_shipment_number( $shipment_number ) {

    global $wpdb;
    $shipment_number = sanitize_text_field( $shipment_number );
    $sql = apply_filters( 'wpcargo_trackform_shipment_number_query', "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE post_title = '{$shipment_number}' AND `post_status` = 'publish' AND `post_type` = 'wpcargo_shipment' LIMIT 1", $shipment_number );
    $results = $wpdb->get_var($sql);
    return $results;
}

function wpcargo_trackform_multiple_shipment_numbers( $shipment_numbers ) {

    global $wpdb;

    $shipment_numbers_array_string = '(';

    for ( $i = 0; $i < count( $shipment_numbers ); $i++ ) {

        $shipment_numbers_array_string .= "'" . $shipment_numbers[$i] . "'";

        if ( $i < ( count( $shipment_numbers ) - 1 ) ) {
            $shipment_numbers_array_string .= ', ';
        }

    }

    $shipment_numbers_array_string .= ')';

    $sql = apply_filters( 'wpcargo_trackform_multiple_shipment_numbers_query', "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE post_title IN {$shipment_numbers_array_string} AND `post_status` = 'publish' AND `post_type` = 'wpcargo_shipment'", $shipment_numbers_array_string );
    $results = $wpdb->get_results($sql, ARRAY_A);
    return $results;

}

function wpcargo_get_postmeta( $post_id = '' , $metakey = '', $type = '' ){
	global $wpcargo;
    $result = '';
    if( !empty( $post_id ) && !empty( $metakey ) ){
        $result  = maybe_unserialize( esc_html( get_post_meta( $post_id, $metakey, true) ) );
        if( is_array( $result ) ){
            $result = array_filter( array_map( 'trim', $result ) );
            if( !empty( $result ) ){
                $result = implode(', ',$result);
            } 
            if( $type == 'url' ){
                $url_data = array_values( maybe_unserialize( get_post_meta( $post_id, $metakey, true) ) );
                $target   = count( $url_data ) > 2 ? '_blank' : '' ;
                $url      = $url_data[1] ? $url_data[1] : '#' ;
                $label    = $url_data[0];
                $result   = '<a href="'.esc_url($url).'" target="'.$target.'">'.esc_html($label).'</a>';
            }       
        }
    }
    return $result;
}
function wpcargo_to_slug( $string = '' ){
    $string = strtolower( preg_replace('/\s+/', '_', trim( $string ) ) );
    return substr( preg_replace('/[^A-Za-z0-9_\-]/', '', $string ), 0, 60 );
}
function wpcargo_html_value( $string, $htmltag = 'span', $attr = 'class' ){
    $string    = trim( $string );
    $attrvalue = strtolower( str_replace(" ", '-', $string ) );
    $attrvalue = preg_replace("/[^A-Za-z0-9 -]/", '', $attrvalue);
    return '<'.$htmltag.' '.$attr.' ="'.$attrvalue.'" >'.$string.'</'.$htmltag.'>';
}
function wpcargo_user_roles_list(){
    $wpcargo_user_roles_list = apply_filters( 'wpcargo_user_roles_list', array(
        'administrator', 'wpc_shipment_manager', 'wpcargo_branch_manager', 'wpcargo_driver', 'wpcargo_client', 'cargo_agent'
    ) );
    return $wpcargo_user_roles_list;
}
function wpcargo_has_registered_shipper(){
    global $wpdb;
    $sql = "SELECT tbl2.meta_value FROM `{$wpdb->prefix}posts` AS tbl1 INNER JOIN `{$wpdb->prefix}postmeta` AS tbl2 ON tbl1.ID = tbl2.post_id WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' AND tbl2.meta_key LIKE 'registered_shipper' AND ( tbl2.meta_value IS NOT NULL AND tbl2.meta_value <> '' ) GROUP BY tbl2.meta_value";
    $result = $wpdb->get_col($sql);
    return $result;
}
function wpcargo_print_fonts(){
    $fonts = array(
        'roboto' => array(
            'url' => 'https://fonts.googleapis.com/css2?family=Roboto&display=swap',
            'fontfamily' => "'Roboto', sans-serif"
        ),
        'montserrat' => array(
            'url' => 'https://fonts.googleapis.com/css2?family=Lato&family=Montserrat&display=swap',
            'fontfamily' => "'Montserrat', sans-serif"
        ),
        'vt323' => array(
            'url' => 'https://fonts.googleapis.com/css2?family=VT323&display=swap',
            'fontfamily' => "'VT323', monospace"
        ),
        'petrona' => array(
            'url' => 'https://fonts.googleapis.com/css2?family=Petrona&display=swap',
            'fontfamily' => "'Petrona', serif"
        ),
        'natosans' => array(
            'url' => 'https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@100;300;400&display=swap',
            'fontfamily' => "'Noto Sans TC', sans-serif"
        )
    );
    return apply_filters( 'wpcargo_print_fonts', $fonts );
}
function wpcargo_email_shortcodes_list(){
    $tags = array(
        '{'.wpcargo_track_meta().'}' => __('Tracking Number','wpcargo'),
        '{wpcargo_shipper_email}'   => __('Shipper Email','wpcargo'),
        '{wpcargo_receiver_email}'  => __('Receiver Email','wpcargo'),
        '{wpcargo_shipper_phone}'   => __('Shipper Phone','wpcargo'),
        '{wpcargo_receiver_phone}'  => __('Receiver Phone','wpcargo'),
        '{admin_email}'             => __('Admin Email','wpcargo'),
        '{wpcargo_shipper_name}'    => __('Name of the Shipper','wpcargo'),
        '{wpcargo_receiver_name}'   => __('Name of the Receiver','wpcargo'),
        '{status}'                  => __('Shipment Status','wpcargo'),
        '{location}'                => __('Location','wpcargo'),
        '{site_name}'               => __('Website Name','wpcargo'),
        '{site_url}'                => __('Website URL','wpcargo'),
        '{wpcreg_client_email}'     => __('Registered Client Email','wpcargo'),
    );
    $tags   = apply_filters( 'wpc_email_meta_tags', $tags );
    return $tags;
}
function wpcargo_default_status(){
    $status = array(
        __( 'Pending', 'wpcargo' ),
        __( 'Picked up', 'wpcargo' ),
        __( 'On Hold', 'wpcargo' ),
        __( 'Out for delivery', 'wpcargo' ),
        __( 'In Transit', 'wpcargo' ),
        __( 'Enroute', 'wpcargo' ),
        __( 'Cancelled', 'wpcargo' ),
        __( 'Delivered', 'wpcargo' ),
        __( 'Returned', 'wpcargo' )
    );
    return apply_filters( 'wpcargo_default_status', $status );
}

function wpcargo_field_generator( $field_data, $field_meta, $value = '', $class='' ){
    $value = esc_html( $value );
	$required = $field_data['required'] == 'true' ? 'required' : '';    
	if( $field_data['field'] == 'textarea' ){
		$field = '<textarea id="'.$field_meta.'" class="'.$class.'" name="'.$field_meta.'" '.$required.'>'.$value.'</textarea>';
	}elseif( $field_data['field'] == 'select' ){
		$field = '<select id="'.$field_meta.'" class="'.$class.'" name="'.$field_meta.'" '.$required.'>';
		$field .= '<option value="">'.esc_html__('-- Select Type --','wpcargo').'</option>';
		if( !empty( $field_data['options'] ) ){
			foreach ( $field_data['options'] as $_value) {
                $_value = esc_html($_value);
				$field .= '<option value="'.trim($_value).'" '.selected( $value, trim(esc_html($_value)), false ).'>'.trim($_value).'</option>';
			}
		}
		$field .= '</select>';
	}elseif( $field_data['field'] == 'radio' ){
		if( !empty( $field_data['options'] ) ){
			$field      = '';
			foreach ( $field_data['options'] as $_value) {
                $_value = esc_html($_value);
                $checked    = $value == $_value ? 'checked' : '' ;
				$field .= '<p><input class="'.$class.'" id="'.$field_meta.'_'.$_value.'" type="'.$field_data['field'].'" name="'.$field_meta.'" value="'.$_value.'" '.$checked.' '.$required.'>';
				$field .= '<label for="'.$field_meta.'_'.$_value.'">'.$_value.'</label></p>';
			}
		}
	}elseif( $field_data['field'] == 'checkbox' ){    
        if( empty( $value ) ){
            $value = array();
        }else{
            $value = is_array( $value ) ? $value : array_map( 'trim', explode(",", $value ) );
        }
		if( !empty( $field_data['options'] ) ){
			$field = '';
			foreach ( $field_data['options'] as $_value) {
                $_value     = esc_html($_value);
                $checked    = in_array( $_value, $value ) ? 'checked' : '' ;
				$field .= '<p><input class="'.$class.'" id="'.$field_meta.'_'.$_value.'" type="'.$field_data['field'].'" name="'.$field_meta.'" value="'.$_value.'" '.$checked.' '.$required.'>';
				$field .= '<label for="'.$field_meta.'_'.$_value.'">'.$_value.'</label></p>';
			}
		}
	}else{
		$field = '<input id="'.$field_meta.'" class="'.$class.'" type="'.$field_data['field'].'" name="'.$field_meta.'" value="'.$value.'" '.$required.'>';
	}
	return apply_filters( 'wpcargo_field_generator_'.$field_meta, $field, $field_data, $field_meta, $value, $class );
}

function wpcargo_email_replace_shortcodes_list( $post_id ){
    $delimiter = array("{", "}");
    $replace_shortcodes = array();
    if( !empty( wpcargo_email_shortcodes_list() ) ){
        foreach ( wpcargo_email_shortcodes_list() as $shortcode => $shortcode_label ) {
            $shortcode = trim( str_replace( $delimiter, '', $shortcode ) );
            if( $shortcode == wpcargo_track_meta() ){
                $replace_shortcodes[] = esc_html( get_the_title($post_id) );
            }elseif( $shortcode == 'admin_email' ){
                $replace_shortcodes[] = apply_filters( 'wpcargo_admin_notification_email_address', get_option('admin_email') );
            }elseif( $shortcode == 'site_name' ){
                $replace_shortcodes[] = esc_html( get_bloginfo('name') );
            }elseif( $shortcode == 'site_url' ){
                $replace_shortcodes[] = esc_url( get_bloginfo('url') );
            }elseif( $shortcode == 'status' ){
                $replace_shortcodes[] = esc_html( get_post_meta( $post_id, 'wpcargo_status', true ) );
            }elseif( $shortcode == 'wpcreg_client_email' ){
                $reg_shipper = (int) esc_html( get_post_meta( $post_id, 'registered_shipper', true ) );
                $user_info   = get_userdata($reg_shipper);
                $reg_email = '';
                if( $user_info ){
                    $reg_email = $user_info->user_email;
                }
                $replace_shortcodes[] = $reg_email;
            }else{
                $meta_value = maybe_unserialize( get_post_meta( $post_id, $shortcode, true ) );
                $meta_value = apply_filters( 'wpcargo_shortcode_meta_value', $meta_value, $shortcode, $post_id );
                if( is_array( $meta_value ) ){
                    $meta_value = implode(', ',$meta_value );
                }
                $replace_shortcodes[] = esc_html( $meta_value );
            }
        }
    }
    return apply_filters( 'wpc_email_replace_shortcodes', $replace_shortcodes, $post_id );
}   

function wpcargo_shipper_meta_filter(){
    return apply_filters( 'wpcargo_shipper_meta_filter', 'wpcargo_shipper_name');
} 
function wpcargo_shipper_label_filter(){
    return apply_filters( 'wpcargo_shipper_label_filter', esc_html__('Shipper Name', 'wpcargo' ) );
} 
function wpcargo_receiver_meta_filter(){
    return apply_filters( 'wpcargo_receiver_meta_filter', 'wpcargo_receiver_name' );
} 
function wpcargo_receiver_label_filter(){
    return apply_filters( 'wpcargo_receiver_label_filter', esc_html__('Receiver Name', 'wpcargo' ) );
} 
function wpcargo_default_client_email_body(){
    ob_start();
    ?>
    <p>Dear {wpcargo_shipper_name},</p>
    <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">We are pleased to inform you that your shipment has now cleared customs and is now {status}.</p>
    <br />
    <h4 style="font-size: 1.2em;">Tracking Information</h4>
    <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">Tracking Number - {<?php echo esc_html( wpcargo_track_meta() ); ?>}</p>
    <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">Location: {location}</p>
    <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">Latest International Scan: Customs status updated</p>
    <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">We hope this meets with your approval. Please do not hesitate to get in touch if we can be of any further assistance.</p>
    <br />
    <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">Yours sincerely</p>
    <p style="font-size: 1em;margin:.5em 0px;line-height: initial;"><a href="{site_url}">{site_name}</a></p>
    <?php
    $output = ob_get_clean();
    return $output;
}
function wpcargo_default_admin_email_body(){
    ob_start();
    ?>
    <p>Dear Admin,</p>
    <p>Shipment number {<?php echo esc_html( wpcargo_track_meta() ); ?>} has been updated to {status}.</p>
    <br />
    <p>Yours sincerely</p>
    <p><a href="{site_url}">{site_name}</a></p>
    <?php
    $output = ob_get_clean();
    return $output;
}
function wpcargo_default_email_footer(){
    ob_start();
    ?>
    <div class="wpc-contact-info" style="margin-top: 10px;">
        <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">Your Address Here...</p>
        <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">Email: <a href="mailto:{admin_email}">{admin_email}</a> - Web: <a href="{site_url}">{site_name}</a></p>
        <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">Phone: <a href="tel:">Your Phone Number Here</a>, <a href="tel:">Your Phone Number Here</a></p>
    </div>
    <div class="wpc-contact-bottom" style="margin-top: 2em; padding: 1em; border-top: 1px solid #000;">
        <p style="font-size: 1em;margin:.5em 0px;line-height: initial;">This message is intended solely for the use of the individual or organisation to whom it is addressed. It may contain privileged or confidential information. If you have received this message in error, please notify the originator immediately. If you are not the intended recipient, you should not use, copy, alter or disclose the contents of this message. All information or opinions expressed in this message and/or any attachments are those of the author and are not necessarily those of {site_name} or its affiliates. {site_name} accepts no responsibility for loss or damage arising from its use, including damage from virus.</p>
    </div>
    <?php
    $output = ob_get_clean();
    return $output;
}
function wpcargo_email_body_container( $email_body = '', $email_footer = '' ){
    global $wpcargo;
    $default_logo       = WPCARGO_PLUGIN_URL.'admin/assets/images/wpcargo-logo-email.png';
    $brand_logo         = !empty( $wpcargo->logo ) ? $wpcargo->logo : $default_logo;
    ob_start();
    include wpcargo_include_template('email.tpl');
    $output = ob_get_clean();
    return $output;
}
function wpcargo_send_email_notificatio( $post_id, $status = '' ){
    wpcargo_client_mail_notification( $post_id, $status );
    wpcargo_admin_mail_notification( $post_id, $status );
}
function wpcargo_client_mail_notification( $post_id, $status = '' ){
    global $wpcargo;
    $wpcargo_mail_domain = !empty( trim( get_option('wpcargo_mail_domain') ) ) ? get_option('wpcargo_mail_domain') : get_option( 'admin_email' ) ;
    if ( $wpcargo->client_mail_active ) {
        $old_status     = get_post_meta($post_id, 'wpcargo_status', true);
        $str_find       = array_keys( wpcargo_email_shortcodes_list() );
        $str_replce     = wpcargo_email_replace_shortcodes_list( $post_id );
        $mail_content   = $wpcargo->client_mail_body;
        $mail_footer    = $wpcargo->client_mail_footer;
        $headers        = array('Content-Type: text/html; charset=UTF-8');
        $attachments    = apply_filters( 'wpcargo_client_email_attachments', $post_id, $status );
        $headers[]      = 'From: ' . get_bloginfo('name') .' <'.$wpcargo_mail_domain.'>';
        if( $wpcargo->mail_cc ){
            $headers[]      = 'cc: '.str_replace($str_find, $str_replce, $wpcargo->mail_cc )."\r\n";
        }
        if( $wpcargo->mail_bcc ){
            $headers[]      = 'Bcc: '.str_replace($str_find, $str_replce, $wpcargo->mail_bcc )."\r\n";
        }
        $subject        = str_replace($str_find, $str_replce, $wpcargo->client_mail_subject );
        $recipients     = str_replace($str_find, $str_replce, $wpcargo->client_mail_to );
        $send_to        = apply_filters( 'wpcargo_client_email_recipients',  $recipients, $post_id, $status );
        $message        = str_replace($str_find, $str_replce, wpcargo_email_body_container( $mail_content, $mail_footer ) );  
        $message        = apply_filters( 'wpcargo_client_mail_notification_message', $message, $post_id );
        if( empty( $wpcargo->mail_status ) ){
            wp_mail( $send_to, $subject, nl2br($message), $headers, $attachments );
        }elseif( !empty( $wpcargo->mail_status ) && in_array( $status, $wpcargo->mail_status) ){
            wp_mail( $send_to, $subject, nl2br($message), $headers, $attachments );
        }   
        do_action( 'wpcargo_after_client_mail_notification', $post_id );
    }
}
function wpcargo_admin_mail_notification( $post_id, $status = ''){
    global $wpcargo;
    $wpcargo_mail_domain = !empty( trim( get_option('wpcargo_admin_mail_domain') ) ) ? get_option('wpcargo_admin_mail_domain') : get_option( 'admin_email' ) ;
    if ( $wpcargo->admin_mail_active ) {
        $str_find       = array_keys( wpcargo_email_shortcodes_list() );
        $str_replce     = wpcargo_email_replace_shortcodes_list( $post_id );
        $mail_content   = $wpcargo->admin_mail_body;
        $mail_footer    = $wpcargo->admin_mail_footer;
        $headers        = array('Content-Type: text/html; charset=UTF-8');
        $attachments    = apply_filters( 'wpcargo_client_email_attachments', $post_id, $status );
        $headers[]      = 'From: ' . get_bloginfo('name') .' <'.$wpcargo_mail_domain.'>';
        $subject        = str_replace($str_find, $str_replce, $wpcargo->admin_mail_subject );
        $recipients     = str_replace($str_find, $str_replce, $wpcargo->admin_mail_to );
        $send_to        = apply_filters( 'wpcargo_admin_email_recipients',  $recipients, $post_id, $status );
        $message        = str_replace($str_find, $str_replce, wpcargo_email_body_container( $mail_content, $mail_footer ) );
        $message        = apply_filters( 'wpcargo_admin_mail_notification_message', $message, $post_id );      
        if( empty( $wpcargo->admin_mail_status ) ){
            $wp_mail = wp_mail( $send_to, $subject, nl2br($message), $headers, $attachments );
        }elseif( !empty( $wpcargo->admin_mail_status ) && in_array( $status, $wpcargo->admin_mail_status) ){
            $wp_mail = wp_mail( $send_to, $subject, nl2br($message), $headers, $attachments );
        }   
        do_action( 'wpcargo_after_admin_mail_notification', $post_id );
    }
}
function wpcargo_pagination( $args = array() ) {    
    $defaults = array(
        'range'           => 4,
        'custom_query'    => FALSE,
        'previous_string' => esc_html__( 'Previous', 'wpcargo' ),
        'next_string'     => esc_html__( 'Next', 'wpcargo' ),
        'before_output'   => '<div id="wpcargo-pagination-wrapper"><nav class="wpcargo-pagination post-nav" aria-label="'.esc_html__('Shipments Pagination', 'wpcargo').'"><ul class="wpcargo-pagination pg-blue justify-content-center">',
        'after_output'    => '</ul></nav></div>'
    );    
    $args = wp_parse_args( 
        $args, 
        apply_filters( 'wpcargo_pagination_defaults', $defaults )
    );    
    $args['range'] = (int) $args['range'] - 1;
    if ( !$args['custom_query'] )
        $args['custom_query'] = @$GLOBALS['wp_query'];


    $count = (int) $args['custom_query']->max_num_pages;
    $page  = intval( get_query_var( 'paged' ) );
    $ceil  = ceil( $args['range'] / 2 );    
    if ( $count <= 1 )
        return FALSE;    
    if ( !$page )
        $page = 1;    
    if ( $count > $args['range'] ) {
        if ( $page <= $args['range'] ) {
            $min = 1;
            $max = $args['range'] + 1;
        } elseif ( $page >= ($count - $ceil) ) {
            $min = $count - $args['range'];
            $max = $count;
        } elseif ( $page >= $args['range'] && $page < ($count - $ceil) ) {
            $min = $page - $ceil;
            $max = $page + $ceil;
        }
    } else {
        $min = 1;
        $max = $count;
    } 
    
    $echo       = '';
    $previous   = intval($page) - 1;
    $previous   = esc_url( get_pagenum_link($previous) );    
    $firstpage  = esc_url( get_pagenum_link(1) );
    if ( $firstpage && (1 != $page) )
        $echo .= '<li class="previous wpcargo-page-item"><a class="wpcargo-page-link waves-effect waves-effect" href="' . $firstpage . '">' . esc_html__( 'First', 'wpcargo' ) . '</a></li>';
    if ( $previous && (1 != $page) )
        $echo .= '<li class="wpcargo-page-item" ><a class="wpcargo-page-link waves-effect waves-effect" href="' . $previous . '" title="' . esc_html__( 'previous', 'wpcargo') . '">' . $args['previous_string'] . '</a></li>';    
    if ( !empty($min) && !empty($max) ) {
        for( $i = $min; $i <= $max; $i++ ) {
            if ($page == $i) {
                $echo .= '<li class="wpcargo-page-item active"><span class="wpcargo-page-link waves-effect waves-effect">' . str_pad( (int)$i, 2, '0', STR_PAD_LEFT ) . '</span></li>';
            } else {
                $echo .= sprintf( '<li class="wpcargo-page-item"><a class="wpcargo-page-link waves-effect waves-effect" href="%s">%002d</a></li>', esc_url( get_pagenum_link($i) ), $i );
            }
        }
    }    
    $next = intval($page) + 1;
    $next = esc_url( get_pagenum_link($next) );
    if ($next && ($count != $page) )
        $echo .= '<li class="wpcargo-page-item"><a class="wpcargo-page-link waves-effect waves-effect" href="' . $next . '" title="' . esc_html__( 'next', 'wpcargo') . '">' . $args['next_string'] . '</a></li>';    
    $lastpage = esc_url( get_pagenum_link($count) );
    if ( $lastpage ) {
        $echo .= '<li class="next wpcargo-page-item"><a class="wpcargo-page-link waves-effect waves-effect" href="' . $lastpage . '">' . esc_html__( 'Last', 'wpcargo' ) . '</a></li>';
    }
    if ( isset($echo) ){
        echo wp_kses( $args['before_output'] . $echo . $args['after_output'] , 'post' );
    }
}
if( !function_exists( 'wpcargo_country_list' )){
    function wpcargo_country_list(){
        return "Afghanistan,Albania,Algeria,American Samoa,Andorra,Angola,Anguilla,Antigua & Barbuda,Argentina,Armenia,Aruba,Australia,Austria,Azerbaijan,Bahamas,The,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bermuda,Bhutan,Bolivia,Bosnia & Herzegovina,Botswana,Brazil,British Virgin Is.,Brunei,Bulgaria,Burkina Faso,Burma,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Cayman Islands,Central African Rep.,Chad,Chile,China,Colombia,Comoros,Congo,Dem. Rep.,Congo,Repub. of the,Cook Islands,Costa Rica,Cote d'Ivoire,Croatia,Cuba,Cyprus,Czech Republic,Denmark,Djibouti,Dominica,Dominican Republic,East Timor,Ecuador,Egypt,El Salvador,Equatorial Guinea,Eritrea,Estonia,Ethiopia,Faroe Islands,Fiji,Finland,France,French Guiana,French Polynesia,Gabon,Gambia,The,Gaza Strip,Georgia,Germany,Ghana,Gibraltar,Greece,Greenland,Grenada,Guadeloupe,Guam,Guatemala,Guernsey,Guinea,Guinea-Bissau,Guyana,Haiti,Honduras,Hong Kong,Hungary,Iceland,India,Indonesia,Iran,Iraq,Ireland,Isle of Man,Israel,Italy,Jamaica,Japan,Jersey,Jordan,Kazakhstan,Kenya,Kiribati,Korea,North,Korea,South,Kuwait,Kyrgyzstan,Laos,Latvia,Lebanon,Lesotho,Liberia,Libya,Liechtenstein,Lithuania,Luxembourg,Macau,Macedonia,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Marshall Islands,Martinique,Mauritania,Mauritius,Mayotte,Mexico,Micronesia,Fed. St.,Moldova,Monaco,Mongolia,Montserrat,Morocco,Mozambique,Namibia,Nauru,Nepal,Netherlands,Netherlands Antilles,New Caledonia,New Zealand,Nicaragua,Niger,Nigeria,N. Mariana Islands,Norway,Oman,Pakistan,Palau,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Poland,Portugal,Puerto Rico,Qatar,Reunion,Romania,Russia,Rwanda,Saint Helena,Saint Kitts & Nevis,Saint Lucia,St Pierre & Miquelon,Saint Vincent and the Grenadines,Samoa,San Marino,Sao Tome & Principe,Saudi Arabia,Senegal,Serbia,Seychelles,Sierra Leone,Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,Spain,Sri Lanka,Sudan,Suriname,Swaziland,Sweden,Switzerland,Syria,Taiwan,Tajikistan,Tanzania,Thailand,Togo,Tonga,Trinidad & Tobago,Tunisia,Turkey,Turkmenistan,Turks & Caicos Is,Tuvalu,Uganda,Ukraine,United Arab Emirates,United Kingdom,United States,Uruguay,Uzbekistan,Vanuatu,Venezuela,Vietnam,Virgin Islands,Wallis and Futuna,West Bank,Western Sahara,Yemen,Zambia,Zimbabwe";
    }
}
function wpcargo_map_script( $callback ){
	$shmap_api = get_option('shmap_api');
	return '<script async defer src="https://maps.googleapis.com/maps/api/js?libraries=geometry,places,visualization&key='.$shmap_api.'&callback='.$callback.'"></script>';
}
function wpcargo_brand_name(){
	return apply_filters('wpcargo_brand_name', esc_html__('WPCargo', 'wpcargo' ) );
}
function wpcargo_general_settings_label(){
	return apply_filters('wpcargo_general_settings_label', esc_html__('General Settings', 'wpcargo' ) );
}
function wpcargo_client_email_settings_label(){
	return apply_filters('wpcargo_email_settings_label', esc_html__('Client Email Settings', 'wpcargo' ) );
}
function wpcargo_admin_email_settings_label(){
    return apply_filters('wpcargo_admin_email_settings_label', esc_html__('Admin Email Settings', 'wpcargo' ) );
}
function wpcargo_shipment_settings_label(){
	return apply_filters('wpcargo_shipment_settings_label', esc_html__('Shipment Settings', 'wpcargo' ) );
}
function wpcargo_report_settings_label(){
	return apply_filters('wpcargo_report_settings_label', esc_html__('Reports', 'wpcargo' ) );
}
function wpcargo_map_settings_label(){
	return apply_filters('wpcargo_map_settings_label', esc_html__('Map Settings', 'wpcargo' ) );
}
function wpcargo_print_layout_label(){
	return apply_filters('wpcargo_print_layout_label', esc_html__('Print Layout', 'wpcargo' ) );
}
function wpcargo_shipment_label(){
	return apply_filters('wpcargo_shipment_label', esc_html__('Shipment Label', 'wpcargo' ) );
}
function wpcargo_shipment_details_label(){
    return apply_filters('wpcargo_shipment_details_label', esc_html__('Shipment Details', 'wpcargo' ) );
}
function wpcargo_history_fields(){
	global $wpcargo;
    $history_fields = array(
        'date' => array(
            'label' => esc_html__('Date', 'wpcargo'),
            'field' => 'text',
            'required' => 'false',
            'options' => array()
        ),
        'time' => array(
            'label' => esc_html__('Time', 'wpcargo'),
            'field' => 'text',
            'required' => 'false',
            'options' => array()
        ),
        'location' => array(
            'label' => esc_html__('Location', 'wpcargo'),
            'field' => 'text',
            'required' => 'false',
            'options' => array()
        ),
        'status' => array(
            'label' => esc_html__('Status', 'wpcargo'),
            'field' => 'select',
            'required' => 'false',
            'options' => $wpcargo->status
        ),
        'updated-name' => array(
            'label' => esc_html__('Updated By', 'wpcargo'),
            'field' => 'text',
            'required' => 'false',
            'options' => array()
        ),
        'remarks' => array(
            'label' => esc_html__('Remarks', 'wpcargo'),
            'field' => 'textarea',
            'required' => 'false',
            'options' => array()
        ),
    );
    return apply_filters( 'wpcargo_history_fields', $history_fields );
}
function wpcargo_barcode_types(){
    $code_type = array(
        'code128', 'code128a', 'code39', 'code25', 'codabar'
    );
    return $code_type;
}
function wpcargo_print_barcode_sizes(){
    $barcode_sizes = array(
        'invoice' => wpcargo_barcode_dim_sizes(),
        'label' => wpcargo_barcode_dim_sizes(),
        'waybill' => wpcargo_barcode_dim_sizes(),
        'bol' => wpcargo_barcode_dim_sizes(),
    );
    return apply_filters('wpcargo_print_barcode_sizes', $barcode_sizes);
}
function wpcargo_barcode_dim_sizes(){
    $sizes = array(
        'height' => get_option('wpcargo_print_barcode_height'),
        'width' => get_option('wpcargo_print_barcode_width'),
    );
    return $sizes;
}
function wpcargo_default_shipment_info(){
	$shipment_info = array(
        'wpcargo_type_of_shipment'	=> esc_html__('Type of Shipment', 'wpcargo'),
        'wpcargo_courier'			=> esc_html__('Courier', 'wpcargo'),
        'wpcargo_carrier_ref_number'	=> esc_html__('Carrier Reference No.', 'wpcargo'),
        'wpcargo_mode_field'			=> esc_html__('Mode', 'wpcargo'),
        'wpcargo_carrier_field'			=> esc_html__('Carrier', 'wpcargo'),
        'wpcargo_packages'				=> esc_html__('Packages', 'wpcargo'),
        'wpcargo_product'				=> esc_html__('Product', 'wpcargo'),
        'wpcargo_weight'				=> esc_html__('Weight', 'wpcargo'),
        'wpcargo_qty'					=> esc_html__('Quantity', 'wpcargo'),
        'wpcargo_total_freight'			=> esc_html__('Total Freight', 'wpcargo'),
        'payment_wpcargo_mode_field'	=> esc_html__('Payment Mode', 'wpcargo'),
        'wpcargo_origin_field'			=> esc_html__('Origin', 'wpcargo'),
        'wpcargo_pickup_date_picker'	=> esc_html__('Pickup Date', 'wpcargo'),
        'wpcargo_destination'			=> esc_html__('Destination', 'wpcargo'),
        'wpcargo_departure_time_picker' => esc_html__('Departure Time', 'wpcargo'),
        'wpcargo_pickup_time_picker'	=> esc_html__('Pickup Time', 'wpcargo'),
        'wpcargo_expected_delivery_date_picker' => esc_html__('Expected Delivery Date', 'wpcargo'),
    );
	return apply_filters( 'wpcargo_default_shipment_info', $history_fields );
}
function wpcargo_assign_shipment_email( $post_id, $user_id, $designation ){
    global  $wpcargo;
    $user_info      = get_userdata( $user_id );
    // Check if user exist 
    if( !$user_info ){
        return false;
    }
	$str_find       = array_keys( wpcargo_email_shortcodes_list() );
	$str_replce     = wpcargo_email_replace_shortcodes_list( $post_id );
	$wpcargo_mail_domain = !empty( trim( get_option('wpcargo_admin_mail_domain') ) ) ? get_option('wpcargo_admin_mail_domain') : get_option( 'admin_email' ) ;

    $user_email = apply_filters( 'wpcargo_assign_email_recipients', $user_info->user_email, $post_id, $user_id, $designation ); 
                     
	$headers        = array('Content-Type: text/html; charset=UTF-8');
    $headers[]      = esc_html__('From: ', 'wpcargo' ) . get_bloginfo('name') .' <'.$wpcargo_mail_domain.'>';
    $mail_footer    = $wpcargo->client_mail_footer;
	ob_start();
		?>
		<p><?php esc_html_e( 'Dear', 'wpcargo' ); ?> <?php echo esc_html( $wpcargo->user_fullname( $user_id ) ); ?>,</p>
        <p><?php echo esc_html__( 'Shipment number ', 'wpcargo' ).get_the_title( $post_id ).esc_html__( ' has been assigned to you.', 'wpcargo' ); ?></p>
		<?php
	$mail_content   = ob_get_clean();
    $mail_content   = apply_filters( 'wpcargo_assign_mail_content', $mail_content, $post_id, $user_id, $designation );
    $message        = str_replace($str_find, $str_replce, wpcargo_email_body_container( $mail_content, $mail_footer ) ); 
    $subject        = esc_html__( 'Assign Shipment Notification', 'wpcargo' ).' ['.$designation.']';
	wp_mail( $user_email, $subject, $message, $headers );
}
function wpc_can_send_email_agent(){
	$gen_settings = get_option( 'wpcargo_option_settings' );
	$email_agent = !array_key_exists('wpcargo_email_agent', $gen_settings ) ? true : false;
	return $email_agent;
}
function wpc_can_send_email_employee(){
	$gen_settings = get_option( 'wpcargo_option_settings' );
	$email_employee = !array_key_exists('wpcargo_email_employee', $gen_settings ) ? true : false;
	return $email_employee;
}
function wpc_can_send_email_client(){
	$gen_settings = get_option( 'wpcargo_option_settings' );
	$email_client = !array_key_exists('wpcargo_email_client', $gen_settings ) ? true : false;
	return $email_client;
}
function wpcargo_history_order( $history ){
    if( empty( $history ) || !is_array( $history ) ){
        return array();
    }
    if( !array_key_exists( 'date', wpcargo_history_fields() ) ){
        return array_reverse( $history );
    }
    $history        = array_filter( $history );
    $sort_by_date   = array();
    $sort_by_time   = array();
    $has_date       = true;
    $has_time       = true;
    usort($history, function( $date_a, $date_b ){
        if( !array_key_exists( 'date', $date_a ) ){
            return $date_a;
        }
        return apply_filters( 'wpcargo_history_order', strcmp($date_a["date"], $date_b["date"]) );
    });
    foreach( $history as $key => $value ){
        if( !array_key_exists( 'date', $value ) ){
            $has_date   = false;
            break;
        }
        $sort_by_date[$value['date']][] = $value;
    }
    if( !$has_date ){
        return apply_filters( 'wpcargo_history_order', array_reverse( $history ) );
    }
    $reverse_date = array_reverse( $sort_by_date ); 

    foreach( $reverse_date as $value ){
        if( is_array( $value ) ){
            if( array_key_exists( 'time', $value[0] ) ){
                usort( $value, function( $time_a, $time_b ){
                    return strcmp($time_a["time"], $time_b["time"]);
                });
                $value = array_reverse( $value );
            }
        }        
        foreach( $value as $time ){
            $sort_by_time[] = $time;
        }
    }
    return apply_filters( 'wpcargo_history_order', $sort_by_time );   
}
function wpcargo_generate_barcodecode( $string ){
    if( empty($string) ){
        return false;
    }
    require_once WPCARGO_PLUGIN_PATH.'lib/barcode-generator/vendor/autoload.php';
    $generator 	= new Picqer\Barcode\BarcodeGeneratorPNG();
    $width 		= apply_filters( 'wpcargo_barcode_url_width', 1 );
    $height 	= apply_filters( 'wpcargo_barcode_url_height', 60 );
    $base64		= 'data:image/png;base64,'.base64_encode($generator->getBarcode( $string, $generator::TYPE_CODE_128, $width, $height ));
    return apply_filters( 'wpcargo_generate_barcodecode', $base64, $string );
}
function wpcargo_generate_qrcode( $string ){
    // include QRcode Library
    require_once WPCARGO_PLUGIN_PATH.'lib/phpqrcode-master/qrlib.php';
    // Set up directory and filename
    $tempDir    = WPCARGO_PLUGIN_PATH.'lib/phpqrcode/';
    $filename   = 'wpcargoqrcode.png';
    $path       = $tempDir.$filename;
    // Create QRCode PNG fileformat
    $level 		= apply_filters( 'wpcargo_qrcode_level', QR_ECLEVEL_M );
    $size 	    = apply_filters( 'wpcargo_qrcode_size', 3 );
    $margin 	= apply_filters( 'wpcargo_qrcode_margin', 1 );
    // $text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false
    QRcode::png($string, $path, $level, $size, $margin );
    $type = pathinfo($path, PATHINFO_EXTENSION);
    // Conver file format into base64
    $data   = file_get_contents($path);
    $base64 =  'data:image/' . $type . ';base64,' . base64_encode($data);
    return apply_filters( 'wpcargo_generate_qrcode', $base64, $string );
}

function wpcargo_display_multiple_results_for_multiple_tracking( $shipment_ids ) {

    ob_start();

    ?>

    <div class="accordion" id="result-accordion">
		<?php
			for ( $i = 0; $i < count( $shipment_ids ); $i++ ):

                $shipment 				= new stdClass;
			    $shipment->ID 			= (int)esc_html( $shipment_ids[$i]['ID'] );
			    $shipment->post_title 	= esc_html( get_the_title( $shipment->ID ) );
			    $shipment_status = esc_html( get_post_meta( $shipment->ID, 'wpcargo_status', true ) );
			    $class_status   = strtolower( $shipment_status );
			    $class_status   = str_replace(' ', '_', $class_status );
			    do_action( 'wpcargo_before_search_result' ); ?>
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading<?php echo( $i ) ?>">
      					<button class="wpcargo-min-width-btn accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo( $i ) ?>" aria-expanded="false" aria-controls="collapse<?php echo( $i ) ?>">
        					<?php echo( $shipment->post_title ); ?>
      					</button>
    				</h2>
					<div id="collapse<?php echo( $i ) ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo( $i ) ?>" data-bs-parent="#result-accordion">
      					<div class="accordion-body">
			        		<div id="wpcargo-result-print" class="wpcargo-wrap-details wpcargo-container <?php echo $class_status;?>">
				        	<?php
							    do_action( 'wpcargo_print_btn' );
					        	do_action('wpcargo_before_track_details', $shipment );
					        	do_action('wpcargo_track_header_details', $shipment );
					    		do_action('wpcargo_track_after_header_details', $shipment );
					    		do_action('wpcargo_track_shipper_details', $shipment );
					    		do_action('wpcargo_before_shipment_details', $shipment );
					    		do_action('wpcargo_track_shipment_details', $shipment );	
					    		do_action('wpcargo_after_package_details', $shipment );
					    		if( wpcargo_package_settings()->frontend_enable ){
						    		do_action('wpcargo_after_package_totals', $shipment );
					    		}
					    		do_action('wpcargo_after_track_details', $shipment );	
				    		?>
							</div>
						</div>
				    </div>
			    </div>
            <?php endfor; ?>
		</div>

    <?php

    ob_end_flush();

}

function wpcargo_display_single_result_for_multiple_tracking( $shipment_ids ) {

    ob_start();

    ?>

    <?php

            $shipment 				= new stdClass;
			$shipment->ID 			= (int)esc_html( $shipment_ids[0]['ID'] );
			$shipment->post_title 	= esc_html( get_the_title( $shipment->ID) );
			$shipment_status = esc_html( get_post_meta( $shipment->ID, 'wpcargo_status', true ) );
			$class_status   = strtolower( $shipment_status );
			$class_status   = str_replace(' ', '_', $class_status );
			do_action( 'wpcargo_before_search_result' );
			do_action( 'wpcargo_print_btn' ); ?>
			<div id="wpcargo-result-print" class="wpcargo-wrap-details wpcargo-container <?php echo $class_status;?>">
				<?php
					do_action('wpcargo_before_track_details', $shipment );
					do_action('wpcargo_track_header_details', $shipment );
					do_action('wpcargo_track_after_header_details', $shipment );
					do_action('wpcargo_track_shipper_details', $shipment );
					do_action('wpcargo_before_shipment_details', $shipment );
					do_action('wpcargo_track_shipment_details', $shipment );	
					do_action('wpcargo_after_package_details', $shipment );
					if( wpcargo_package_settings()->frontend_enable ){
						do_action('wpcargo_after_package_totals', $shipment );
					}
					do_action('wpcargo_after_track_details', $shipment );	
				?>
			</div>

    <?php

    ob_end_flush();

}

function get_page_id_by_title( $page_title, $output = OBJECT, $post_type = 'page'  ){
    global $wpdb;

	if ( is_array( $post_type ) ) {
		$post_type           = esc_sql( $post_type );
		$post_type_in_string = "'" . implode( "','", $post_type ) . "'";
		$sql                 = $wpdb->prepare(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type IN ($post_type_in_string)",
			$page_title
		);
	} else {
		$sql = $wpdb->prepare(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type = %s",
			$page_title,
			$post_type
		);
	}

	$page = $wpdb->get_var( $sql );

	if ( $page ) {
		return get_post( $page, $output );
	}

	return NULL;
}

function wpcargo_file_contents_exist($url, $response_code = 200)
{
    $headers = get_headers($url);

    if (substr($headers[0], 9, 3) == $response_code)
    {
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}