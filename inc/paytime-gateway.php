<?php
class WC_Paytime_Gateway extends WC_Payment_Gateway {

	/**
	 * Class constructor, more about it in Step 3
	 */
	public function __construct() {

		$this->id = 'paytime-payment'; // payment gateway plugin ID
		$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields = true; // in case you need a custom credit card form
		$this->method_title = 'PayTime Payment';
		$this->method_description = 'PayTime Payment Gateway'; // will be displayed on the options page

		// gateways can support subscriptions, refunds, saved payment methods,
		// but in this tutorial we begin with simple payments
		$this->supports = array(
			'products'
		);

		// Method with all the options fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->order_status = $this->get_option('order_status');

		if (!$this->is_valid_for_use()) {
			$this->enabled = 'no';
		}else{
			$this->enabled = $this->get_option( 'enabled' );
		}

		$this->testmode = $this->get_option( 'testmode' );
		if($this->testmode == 'no'){
			$this->api_username = $this->get_option( 'api_username' );
			$this->api_password = $this->get_option( 'api_password' );
			$this->api_client_id = $this->get_option( 'api_client_id' );
			$this->api_client_secret = $this->get_option( 'api_client_secret' );
			$this->api_url = 'https://apigateway.paytime.com.np/';
		}else{
			$this->api_username = $this->get_option( 'sandbox_api_username' );
			$this->api_password = $this->get_option( 'sandbox_api_password' );
			$this->api_client_id = $this->get_option( 'sandbox_api_client_id' );
			$this->api_client_secret = $this->get_option( 'sandbox_api_client_secret' );
			$this->api_url = 'https://uatapigateway.paytime.com.np/';
		}

		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action('woocommerce_receipt_paytime-payment', array($this, 'receipt_page'));
		//add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'verify_payment' ) );
		add_action( 'woocommerce_before_thankyou', array( $this, 'verify_payment' ) );

	}

	/**
	 * Plugin options, we deal with it in Step 3 too
	 */
	public function init_form_fields(){

		$this->form_fields = array(
			'enabled' => array(
				'title'       => __('Enable/Disable','paytime'),
				'label'       => __('Enable PayTime Gateway','paytime'),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __('Title','paytime'),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.','paytime'),
				'default'     => 'PayTime Payment',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __('Description','paytime'),
				'type'        => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.','paytime'),
				'default'     => 'Pay with PayTime payment gateway.',
			),
			'order_status' => array(
				'title'       => __('Order Status','paytime'),
				'type'        => 'select',
				'options'     => array(
					'processing' => __('Processing','paytime'),
					'completed' => __('Completed','paytime')
				),
				'description' => __('Select order status after successful payment from PayTime.','paytime'),
				'default'     => 'completed',
			),
			'testmode'              => array(
				'title'       => __( 'PayTime sandbox', 'paytime' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Paytime sandbox', 'paytime' ),
				'default'     => 'no',
				/* translators: %s: URL */
				'description' => sprintf( __( 'PayTime sandbox can be used to test payments. Sign up for a <a href="%s" target="_blank">developer account</a>.', 'paytime' ), 'https://uatapigateway.paytime.com.np/' ),
			),
			'api_details'           => array(
				'title'       => __( 'API credentials', 'paytime' ),
				'type'        => 'title',
				/* translators: %s: URL */
				'description' => sprintf( __( 'Enter your PayTime API credentials to process payments via PayTime. Learn how to access your <a href="%s" target="_blank">PayTime API Credentials</a>.', 'paytime' ), 'https://uatapigateway.paytime.com.np/Help' ),
			),
			'api_username' => array(
				'title'       => __('API Username','paytime'),
				'type'        => 'text'
			),
			'api_password' => array(
				'title'       => __('API Password','paytime'),
				'type'        => 'password'
			),
			'api_client_id' => array(
				'title'       => __('Merchant ID','paytime'),
				'type'        => 'text'
			),
			'api_client_secret' => array(
				'title'       => __('API Secret','paytime'),
				'type'        => 'password'
			),
			'sandbox_api_username' => array(
				'title'       => __('Sandbox API Username','paytime'),
				'type'        => 'text'
			),
			'sandbox_api_password' => array(
				'title'       => __('Sandbox API Password','paytime'),
				'type'        => 'password'
			),
			'sandbox_api_client_id' => array(
				'title'       => __('Sandbox Merchant ID','paytime'),
				'type'        => 'text'
			),
			'sandbox_api_client_secret' => array(
				'title'       => __('Sandbox API Secret','paytime'),
				'type'        => 'password'
			)
		);

	}

	public function get_icon() {

		$icon_html = '<img src="' . PTIME_URL . '/assets/images/paytime.png" alt="' . esc_attr__( 'PayTime Payment Logo', 'paytime' ) . '" />';

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

	/**
	 * Payment gateway enabled only for Nepalese currency
	 */
	public function is_valid_for_use()
	{
		return in_array(get_woocommerce_currency(), apply_filters('woocommerce_paytime_supported_currencies', array('NPR')), true);
	}

	public function admin_options()
	{
		if ($this->is_valid_for_use()) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
					<strong><?php esc_html_e('Gateway Disabled', 'paytime'); ?></strong>: <?php esc_html_e('PayTime does not support your store currency.', 'paytime'); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * You will need it if you want your custom credit card form, Step 4 is about it
	 */
	public function payment_fields() {
	 	// ok, let's display some description before the payment form
		if ( $this->description ) {

			echo wpautop( wp_kses_post( $this->description ) );
		}

		// I will echo() the form, but you can close PHP tags and print it directly in HTML
		echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

		echo '<div class="clear"></div></fieldset>';

	}

	public function paytime_signature($order_id,$amount,$ru){


		$sig_values = ($amount.$this->api_username.$this->api_client_id.$order_id.$ru.'PaytimePayment');
		//print_r($ru);
		$signature = hash_hmac('sha512',$sig_values,$this->api_client_secret);
		return $signature;

	}

    /**
     * Generate the button link
     **/
    public function generate_paytime_payment_form( $order_id ) {
		global $woocommerce;

		// we need it to get any order details
		$order = wc_get_order( $order_id );


		/*
	 	 * Array with parameters for API interaction
		 */
		$total_amount = wc_format_decimal($order->get_total(), 0);
		$ru = wc_get_endpoint_url('order-received','', get_permalink( get_option('woocommerce_checkout_page_id')));//$this->get_return_url( $order );
		$sign = $this->paytime_signature($order_id,$total_amount,$ru);
		$payment_data = array(
			'Amount' => $total_amount,
			'MerchantApiUser' => sanitize_text_field( $this->api_username ),
			'MerchantId' => sanitize_text_field( $this->api_client_id ),
		    'MerchantTxnId' => $order_id,
		    'TransactionRemarks' => 'PaytimePayment',
		    'RU' => $ru,
		    'Signature' => $sign
		    
		);
		

        $form_input_data = array();

        foreach ($payment_data as $key => $value) {
            $form_input_data[] = '<input type="hidden" name="'.esc_attr($key).'" value="'. esc_attr($value) .'" /><br>';
        }

    	return '<form action="'.esc_url($this->api_url).'Payment/Index" method="post">
                ' . implode('', $form_input_data) . '
                <input type="submit" class="button-alt" id="submit_Paytime_payment_form" value="'.__('Pay via Paytime', 'paytime').'" /> <a class="button cancel" href="'.esc_url($order->get_cancel_order_url()).'">'.__('Cancel order &amp; restore cart', 'paytime').'</a>
            </form>
            <div class="paytime-redirect-message"><div class="message-wrap">'.__("Thank you for your order. We are now redirecting you to Paytime to make payment.", "paytime").'</div></div>
            ';           

    }

    /**
     * receipt_page
     **/
    function receipt_page( $order ) {

        echo '<p>'.__('Please click the button below to pay with Paytime.', 'paytime').'</p>';

        echo $this->generate_paytime_payment_form( $order );
        $order_id = isset($_GET['MerchantTxnId']) ? sanitize_text_field($_GET['MerchantTxnId']) : '';

    }

	/*
	 * We're processing the payments here, everything about it is in Step 5
	 */
	public function process_payment( $order_id ) {

		global $woocommerce;

		// we need it to get any order details
		$order = wc_get_order( $order_id );


		$redirect = $order->get_checkout_payment_url( true );
        return array(
            'result'    => 'success',
            'redirect'  => $redirect
        );

	}


	public function verify_payment($order_id){
		global $woocommerce;
		// we need it to get any order details
		$order = wc_get_order( $order_id );
		if('paytime-payment' == $order->get_payment_method()){

			/*
		 	 * Array with parameters for API interaction
			 */

			$sig_values = ($this->api_username.$this->api_client_id.$order_id);
			$sign = hash_hmac('sha512',$sig_values,$this->api_client_secret);
			$data = array(
			  'MerchantApiUser' => sanitize_text_field( $this->api_username ),
			  'MerchantId' => sanitize_text_field( $this->api_client_id ),
			  'MerchantTxnId' => $order_id,
			  'Signature' => $sign
			);
			$response = wp_remote_post(esc_url($this->api_url).'CheckTransactionStatus', 
			  [     
			    'headers'     => [  
			      'Content-Type'  => 'application/json',
			      'Authorization' => 'Basic ' . base64_encode($this->api_username.':'.$this->api_password),     
			    ],  
			    'body'        => wp_json_encode($data),     
			    'method'      => 'POST',      
			    'data_format' => 'body', 
			    'timeout'     => 45,
			    'redirection' => 5,
			    'httpversion' => '1.0',
			    'blocking'    => true,
			  ] 
			);

			if( !is_wp_error( $response ) ) {

				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				$message = isset($body['message'])?$body['message']:'';
				$status = isset($body['data']['Status'])?$body['data']['Status']:'';
				if ( $message == 'Success' && $status == 'Success') {

					// Set order status to completed
					$order->update_status( $this->order_status, sprintf( __( 'PayTime payment successfully.', 'paytime' ) ) );
					wc_reduce_stock_levels($order_id);
					//$order->add_order_note( $body['data']['TransactionRemarks'], true );
					$woocommerce->cart->empty_cart();

				} else {
					if(isset($body['errors']['error_message'])){
						$error_message = $body['errors']['error_message'];
					}else{
						$error_message = __( 'PayTime payment failed.', 'paytime' );
					}
					$order = wc_get_order( $order_id );
					// Set order status to payment failed
					$order->update_status( 'failed', $error_message );
					wc_add_notice( $error_message, 'error' );
					//$woocommerce->cart->empty_cart();
					wp_redirect(get_permalink( get_option('woocommerce_checkout_page_id')));
					exit;

				}
			}else {
				$order = wc_get_order( $order_id );
				// Set order status to payment failed
				$order->update_status( 'failed', sprintf( __( 'Connection Error!', 'paytime' ) ) );
				wc_add_notice( __( 'Connection Error!', 'paytime' ), 'error' );
				//$woocommerce->cart->empty_cart();
				wp_redirect(get_permalink( get_option('woocommerce_checkout_page_id')));
				exit;
			}		
		}

		//die();
	}

}