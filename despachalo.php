<?php

/**
 * Plugin Name: Despachalo Shipping for WooCommerce
 * Plugin URI: https://despachalo.cl/
 * Description: Método de envió de Despachalo para WooCommerce
 * Version: 1.0.0
 * Author: Despachalo SPA
 * Author URI: https://despachalo.cl/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 * Text Domain: despachalo
 * 
 * WC requires at least: 5.0.0
 * WC tested up to: 6.2.2
 */



/**
 * Plugin global API URL
 */
date_default_timezone_set('America/Santiago');

function wc_despachalo_shipping_start()
{
	global $wp_session;
}

add_action('init', 'wc_despachalo_shipping_start');


require_once('includes/functions.php');

/**
 * WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	/**
	 * Include shipping file.
	 *
	 * @access public
	 * @return void
	 */
	function wc_despachalo_shipping_init()
	{
		include_once('includes/WC_Shipping_Despachalo.php');
	}
	add_action('woocommerce_shipping_init', 'wc_despachalo_shipping_init');

	/**
	 * wc_despachalo_shipping_add_method function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function wc_despachalo_shipping_add_method($methods)
	{
		$methods['despachalo_rest'] = 'WC_Shipping_Despachalo';
		return $methods;
	}

	add_filter('woocommerce_shipping_methods', 'wc_despachalo_shipping_add_method');

}

add_action('wc_despachalo_shipping_api', 'callback_handler');//wc_despachalo_shipping_api

function callback_handler()
{
	header('HTTP/1.1 200 OK');
	$postBody = file_get_contents('php://input');

	$responseipn = json_decode($postBody);


	exit();
	//update_option('webhook_debug', $_POST);
}