<?php
/*
**
** Plugin Name: WooCommerce Price & Weight Free Shipping
** Plugin URI: https://www.wpcare.gr
** Description: A WooCommerce Plugin to enable free shipping for specified minimum price and maximum weight orders.
** Version: 1.0.0
** Author: WPCARE
** Author URI: https://www.wpcare.gr
** License: GNU General Public License v3.0
**
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
** Check if woocommerce is active.
*/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

function woo_price_weight_init() {

	if (is_cart() OR is_checkout()) {
		global $woocommerce;
		$cart_price_total =  NumberFormat( $woocommerce->cart->get_cart_total() );		
		
		if ( $woocommerce->cart->cart_contents_weight < (float)get_option( 'woo_plugin_max_weight' ) 
				AND $cart_price_total > (float)get_option( 'woo_plugin_min_price' )
				AND get_option( 'price_weight_free_shipping_enabled' ) == "yes" ) {
			
			if (is_cart()) { 		
				wc_print_notice( __( get_option( 'price_weight_free_shipping_message' ), 'woocommerce' ), 'success' );
			}
			
			add_action( 'woocommerce_before_checkout_form', 'price_weight_free_shipping_notice', 9 );
			function price_weight_free_shipping_notice() {
				wc_print_notice( __( get_option( 'price_weight_free_shipping_message' ), 'woocommerce' ), 'success' );
			}
			
		}
	}

if ( !class_exists( 'WC_Price_Weight_Free_Shipping' ) ) {
class WC_Price_Weight_Free_Shipping extends WC_Shipping_Method {

/*
** Constructor for Price Weight Free Shipping Method
*/
public function __construct() {
$this->id = 'price_weight_free_shipping'; // Id for your shipping method. Should be uunique.
$this->method_title = __( 'Price & Weight Free Shipping' ); // Title shown in admin
$this->method_description = __( 'Allow users to get free shipping for their orders based on price and weight!' ); // Description shown in admin
$this->title = $this->get_option( 'title' );
$this->enabled = $this->get_option( 'enabled' );
$this->description = $this->get_option( 'description_msg' );
$this->max_weight = $this->get_option( 'max_weight' );
$this->min_price = $this->get_option( 'min_price' );
update_option( 'woo_plugin_max_weight', $this->max_weight ); 
update_option( 'woo_plugin_min_price', $this->min_price ); 
update_option( 'price_weight_free_shipping_enabled', $this->enabled ); 
update_option( 'price_weight_free_shipping_message', $this->description );
 
$this->init();
}
 
/*
** Initialize settings
*/
function init() {
$this->init_form_fields();
$this->init_settings();

// Save settings in admin if you have any defined
add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
}

	/*
	** init admin form fields
	*/
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Free Shipping based on Price & Weight', 'woocommerce' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Free Shipping', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'max_weight' => array(
				'title'       => __( 'Maximum Weight', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The maximum weight that order is entitled for free shipping.', 'woocommerce' ),
				'default'     => __( '10.01', 'woocommerce' ),
				'desc_tip'    => true,
			),			
			'min_price' => array(
				'title'       => __( 'Minimum Price', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The minimum price that order is entitled for free shipping.', 'woocommerce' ),
				'default'     => __( '299.99', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description_msg' => array(
				'title'       => __( 'Message to Customer', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'The message that customer will see at checkout when he selects large volume products.', 'woocommerce' ),
				'default'     => __( 'Free Shipping based on Price and Weight', 'woocommerce' ),
				'desc_tip'    => true,
			),			
		);
    }
 
 
/*
** Calculate Shipping cost.
*/
public function calculate_shipping( $package ) {

global $woocommerce;
$cart_price_total =  NumberFormat( $woocommerce->cart->get_cart_total() );
if ($woocommerce->cart->cart_contents_weight < (float)$this->max_weight
	AND $cart_price_total > (float)$this->min_price) { $rate = array(
'id' => $this->id,
'label' => $this->title,
'cost' => '0',
'calc_tax' => 'per_order'
);
		
}
$this->add_rate( $rate );
}
}
}
}
 
add_action( 'woocommerce_shipping_init', 'woo_price_weight_init' );
 
	function price_weight_free_shipping_method( $methods ) {		
		$methods[] = 'WC_Price_Weight_Free_Shipping';
		return $methods;		
	}
 
	add_filter( 'woocommerce_shipping_methods', 'price_weight_free_shipping_method' );	

}

	/**
	* woocommerce_package_rates is a 2.1+ hook
	*/
	add_filter( 'woocommerce_package_rates', 'hide_shipping_when_free_is_available', 10, 2 );
 
	function hide_shipping_when_free_is_available( $rates, $package ) {
 	
			// Only modify rates if free_shipping is present
			if ( isset( $rates['price_weight_free_shipping'] ) ) {
			
				// To unset a single rate/method, do the following. This example unsets flat_rate shipping
				unset( $rates['flat_rate'] );
				
				// To unset all methods except for free_shipping, do the following
				$free_shipping          = $rates['price_weight_free_shipping'];
				$rates                  = array();
				$rates['price_weight_free_shipping'] = $free_shipping;
			}
			
			return $rates;
	}

    function NumberFormat($target){
		$target = preg_replace('#[^\d.,]#', '', $target); 
		
		$decimals = (get_option('woocommerce_price_num_decimals')+1)*(-1);
		
		if (substr($target, $decimals, 1) == ',') {
			$target = str_replace(".","",$target);
			$target = str_replace(" ","",$target);
			$target = str_replace("'","",$target);
			$target = str_replace(",",".",$target);
		}
		
        return $target;
    }