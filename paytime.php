<?php
defined( 'ABSPATH' ) or die( "No script kiddies please!" );
/**
Plugin Name: Nepal PayTime
Plugin URI: https://www.paytime.com.np/
Description: PayTime plugin enables any user with a wordpress site to integrate PayTime payment gateway in their website/web application. This is extension to WooCommerce Plugin.
Version: 1.0.0
Author: https://www.paytime.com.np/
Author URI:        
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path: /languages/
Text Domain: paytime         
**/
/** Necessary Constants **/
defined( 'PTIME_VERSION' ) or define( 'PTIME_VERSION', '1.0.0' ); //plugin version
defined( 'PTIME_PATH' ) or define( 'PTIME_PATH', plugin_dir_path( __FILE__ ) );
defined( 'PTIME_URL' ) or define( 'PTIME_URL', plugin_dir_url( __FILE__ ) );

if(!class_exists('PayTime')) :

	class PayTime {

		public function __construct() {

			add_action('init', array($this, 'load_text_domain'));

			add_action('admin_notices',  array($this, 'paytime_admin_notices'));
			add_filter( 'woocommerce_payment_gateways', array($this,'paytime_add_gateway_class') );
            add_action('init', array($this, 'paytime_init_gateway_class'));
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
            add_action('template_redirect',array($this,'check_paytime_return_url'));

		}
		public function check_paytime_return_url(){
			
			if( !is_wc_endpoint_url( 'order-received' ) || empty( $_GET['MerchantTxnId'] ) ) {
				return;
			}
			$order_id = sanitize_text_field($_GET['MerchantTxnId']);
			$order = wc_get_order( $order_id );

			if( 'paytime-payment' == $order->get_payment_method() ) { /* WC 3.0+ */
				$url = $order->get_checkout_order_received_url();
				wp_redirect( $url );
				exit;
			}
		}	
		/**
	     * Loads Plugin Text Domain
	     * 
	     */
	    public function load_text_domain() {
	        load_plugin_textdomain('paytime', false, basename(dirname(__FILE__)) . '/languages');
	    }

		/*
		 * This action hook registers our PHP class as a WooCommerce payment gateway
		 */
		
		public function paytime_add_gateway_class( $gateways ) {
			$gateways[] = 'WC_Paytime_Gateway';
			return $gateways;
		}

	    /* Include Payment gateway */
	    public function paytime_init_gateway_class(){
	    	if(defined('WC_VERSION')){
				require PTIME_PATH.'/inc/paytime-gateway.php';
			}
		}

		public function admin_scripts() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( 'woocommerce_page_wc-settings' !== $screen_id ) {
				return;
			}

			wp_enqueue_script( 'woocommerce_paytime_admin', PTIME_URL . 'assets/js/admin.js', array('jquery'), PTIME_VERSION, true );
		}

		public function frontend_scripts() {
			if(defined('WC_VERSION')){
				if(is_checkout()){
					wp_enqueue_style('woocommerce_paytime_front',PTIME_URL . 'assets/css/custom.css',array(),PTIME_VERSION);
				
					wp_enqueue_script( 'woocommerce_paytime_frontend', PTIME_URL . 'assets/js/custom.js', array('jquery'), PTIME_VERSION, true );
				}
			}
		}

        public function paytime_admin_notices() {
        	if(!defined('WC_VERSION')){
	            $class = 'notice notice-error';
	            $message = __('PayTime requires WooCommerce Plugin to be installed and active.', 'paytime');
	            printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
        	}
        }
	}

	$paytime_obj = new PayTime();
endif;
