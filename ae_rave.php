<?php
/*
Plugin Name: Flutterwave Rave for Enginethemes (FreelanceEngine 1.7+ & 1.8+)
Plugin URI: http://rave.flutterwave.com/
Description: Integrates the Flutterwave Rave payment gateway to FreelanceEngine site 1.7+, 1.8+
Version: 1.0
Author: Chigbo Ezejiugo (HRH)
Author URI: http://github.com/emmajiugo
License: GPLv2
*/

add_filter('ae_admin_menu_pages','ae_rave_add_settings', 10, 2 );
function ae_rave_add_settings($pages){
	$sections = array();
	$options = AE_Options::get_instance();

	/**
	 * ae fields settings
	 */
	$sections = array(
		'args' => array(
			'title' => __("Rave Payment", ET_DOMAIN) ,
			'id' => 'meta_field',
			'icon' => 'F',
			'class' => ''
		) ,

		'groups' => array(
			array(
				'args' => array(
					'title' => __("Rave Payment Settings", ET_DOMAIN) ,
					'id' => 'secret-key',
					'class' => '',
					'desc' => __('Get your api keys from your Rave dashboard settings, under "Settings/Api" Tab.<br> For your <b>Test Keys</b> visit https://ravesandbox.flutterwave.com while for your <b>Live Keys</b> visit https://rave.flutterwave.com', ET_DOMAIN),
					'name' => 'rave'
				) ,
				'fields' => array(
					array(
                        'id' => 'mode',
                        // 'type' => 'radio',
                        'label' => __("Mode", ET_DOMAIN),
                        'title' => __("Mode", ET_DOMAIN),
                        'name' => 'mode',
                        'class' => '',
                        'type' => 'select',
                            'data' => array(
                                'disable' => __("Disable", ET_DOMAIN) ,
                                'test' => __("Test", ET_DOMAIN) ,
                                'live' => __("Live", ET_DOMAIN) ,
                            ) ,
                    ),
                    array(
						'id' => 'rave_tsk',
						'type' => 'text',
						'label' => __("Rave Test Secret Key", ET_DOMAIN) ,
						'name' => 'rave_tsk',
						'class' => ''
					),
					array(
						'id' => 'rave_tpk',
						'type' => 'text',
						'label' => __('Rave Test Public Key', ET_DOMAIN),
						'name'  => 'rave_tpk',
						'class' => ''
					),
					array(
						'id' => 'rave_lsk',
						'type' => 'text',
						'label' => __("Rave Live Secret Key", ET_DOMAIN) ,
						'name' => 'rave_lsk',
						'class' => ''
					),
					array(
						'id' => 'rave_lpk',
						'type' => 'text',
						'label' => __('Rave Live Public Key', ET_DOMAIN),
						'name'  => 'rave_lpk',
						'class' => ''
					),
					array(
						'id' => 'modal_title',
						'type' => 'text',
						'label' => __('Custom Modal Title (Optional)', ET_DOMAIN),
						'name'  => 'modal_title',
						'class' => ''
					),
					array(
						'id' => 'modal_desc',
						'type' => 'text',
						'label' => __('Custom Modal Description (Optional)', ET_DOMAIN),
						'name'  => 'modal_desc',
						'class' => ''
					),
					array(
						'id' => 'logo',
						'type' => 'text',
						'label' => __('Custom Logo (Optional)', ET_DOMAIN),
						'name'  => 'logo',
						'class' => ''
					)
					
					
				)
			)
		)
	);

	$temp = new AE_section($sections['args'], $sections['groups'], $options);

	$rave_setting = new AE_container(array(
		'class' => 'field-settings',
		'id' => 'settings',
	) , $temp, $options);

	$pages[] = array(
		'args' => array(
			'parent_slug' => 'et-overview',
			'page_title' => __('Rave', ET_DOMAIN) ,
			'menu_title' => __('RAVE PAYMENT', ET_DOMAIN) ,
			'cap' => 'administrator',
			'slug' => 'ae-rave',
			'icon' => '$',
			'desc' => __("Integrate the Rave payment gateway to your site", ET_DOMAIN)
		) ,
		'container' => $rave_setting
	);
	return $pages;
}


add_filter( 'ae_support_gateway', 'ae_rave_add' );
function ae_rave_add($gateways){
	$gateways['rave'] = 'Rave';
	return $gateways;
}

add_action('after_payment_list', 'ae_rave_render_button');
function ae_rave_render_button() {
	$rave = ae_get_option('rave');
	if($rave['mode'] ==  'disable')
		return false;
?>
	<li>
		<span class="title-plan select-payment" data-type="rave">
			<?php _e("Pay with Rave", ET_DOMAIN); ?>
			
		</span>
		<br>
		<img src="<?php echo plugins_url( 'rave.png' , __FILE__ ); ?>" alt="cardlogos" style="width: 200px !important;"/>
			
		<a href="#" class="btn btn-submit-price-plan select-payment" style="display:block;" data-type="rave"><?php _e("Select", ET_DOMAIN); ?></a>
	</li>
<?php
}

add_filter('ae_setup_payment', 'ae_rave_setup_payment', 10, 3);
function ae_rave_setup_payment($response, $paymentType, $order) {
	// global $current_user, $user_email;
    
    if ($paymentType == 'rave') {
        $rave = ae_get_option('rave');
		$mode = $rave['mode'];
 		if ($mode == 'test') {
			$public_key = $rave['rave_tpk'];
			$baseUrl = 'https://ravesandboxapi.flutterwave.com';
		}else{
			$public_key = $rave['rave_lpk'];
			$baseUrl = 'https://api.ravepay.co';
		}

        
        $order_pay = $order->generate_data_to_pay();
        $orderId = $order_pay['product_id'];
        $amount = $order_pay['total'];
        $currency = $order_pay['currencyCodeType'];
        $pakage_info = array_pop($order_pay['products']);
        $pakage_name = $pakage_info['NAME'];
        $rave_info = ae_get_option('rave');
        $new_id = $order_pay['ID'];
		$txnref	= $new_id . '_' .time();
		$modal_title = $rave['modal_title'];
		$modal_desc = $rave['modal_desc'];
		$logo = $rave['logo'];

		// et_write_session( 'order_id', $new_id );
		
        $return_url = et_get_page_link('process-payment', array(
                        'paymentType' => 'rave',
                        // 'return' => "1",
                        // 'order-id' =>  $new_id
                    )) ;

		/**
		 * New addition
		 */
		
		$headers = array(
			'content-type'	=> 'application/json',
			'cache-control' => 'no-cache'
		);
		//Create Plan
		$body = array(
			'customer_email' => 'e@x.com', //$user_email,
			'amount' => $amount,
			'currency'	=> $txnref,
			'PBFPubKey' => $public_key,
			'txref' => $txnref,
			'redirect_url' => $return_url,
			'custom_logo' => $logo,
			'custom_title' => $modal_title,
			'custom_description' => $modal_desc

			// 'metadata' => json_encode(array('custom_fields' => $meta )),

		);
		$args = array(
			'body'		=> json_encode( $body ),
			'headers'	=> $headers,
			'timeout'	=> 60
		);

		// set new baseUrl to endpoint
		$new_baseUrl = $baseUrl.'/flwv3-pug/getpaidx/api/v2/hosted/pay';

		// cURL using wordpress
		$request = wp_remote_post( $new_baseUrl, $args );
		
		if( ! is_wp_error( $request )) {
			$rave_response = json_decode(wp_remote_retrieve_body($request));

			$url	= $return_url; //$rave_response->data->authurl;
			$order->update_order();
			$response = array(
                'success' => true,
                'data' => array(
                    'url' => $url,
                    'ACK' => true,
                ) ,
                'paymentType' => 'RAVE'
            );
			
		}else{
			$response = array(
                'success' => false,
                'data' => array(
                    'url' => site_url('post-place') ,
                    'ACK' => false
                )
            );
		}
        
      
    }
    return $response;
}
add_filter('ae_process_payment', 'ae_rave_process_payment', 10 ,2 );
function ae_rave_process_payment($payment_return, $data) {
	$rave = ae_get_option('rave');
	$mode = $rave['mode'];
	
	if ($mode == 'test') {
		$baseUrl = 'https://ravesandboxapi.flutterwave.com';
		$secret_key = $rave['rave_tsk'];
	}else{
		$baseUrl = 'https://api.ravepay.co';
		$secret_key = $rave['rave_lsk'];
	}

    $paymenttype = $data['payment_type'];
    $order = $data['order'];
    $order_pay = $order->generate_data_to_pay();
    
    $main_order_id = $order_pay['ID'];
    $main_amount = $order_pay['total'];
   
		// die();
    if($paymenttype == 'rave') { //&& isset($_GET['txref'])){
      	
        $reference = $_GET['txref'];

       	$rave_verify_url = $baseUrl.'/flwv3-pug/getpaidx/api/v2/verify';

		$headers = array(
			'content-type: application/json'
		);

		$args = array(
			'headers'	=> $headers,
			'timeout'	=> 60,
			'SECKEY'    => $secret_key,
			'txref'		=> $reference
		);

		$request = wp_remote_get( $rave_verify_url, $args );
		if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {

            	$rave_response = json_decode( wp_remote_retrieve_body( $request ) );

				if ( 'success' == $rave_response->status ) {

					
					$order_details 	= explode( '_', $rave_response->data->txref );

					$order_id = (int) $order_details[0];

						$amount_paid	= $rave_response->data->amount;

		        		if ( $main_amount !=  $amount_paid ) {
							$payment_return = array(
				                'ACK' => false,
				                'payment' => 'rave',
				                'payment_status' => 'fail',
			                	'msg' => 'Wrong amount paid'

			                );
						} else {
							$payment_return = array(
				                'ACK' => true,
				                'payment' => 'rave',
				                'payment_status' => 'Completed'
			                );
			                wp_update_post( array(
								'ID'          => $order_id,
								'post_status' => 'publish'
							) );
							update_post_meta( $order_id, 'et_paid', 1 );
						}

				} else {
					$payment_return = array(
		                'ACK' => false,
		                'payment' => 'rave',
		                'payment_status' => 'fail',
		                'msg' => "Couldn't Verify Transaction"
	                );
				

				}

	        }
    }
    
    return $payment_return;
}
