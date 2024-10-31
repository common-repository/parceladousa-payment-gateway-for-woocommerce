<?php

/**
 * Plugin Name: ParceladoUSA Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/ParceladoUSA/woocommerce-gateway-parceladousa/
 * Description: Accept payments from Brazilians in your store through ParceladoUSA.
 * Version: 1.1.4
 * Author: ParceladoUSA
 * Author URI: https://parceladousa.com
 * Text Domain: woocommerce-gateway-parceladousa
 * Domain Path: /languages
 *
 * WC requires at least: 5.4
 * WC tested up to: 6.3.1
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if(!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

add_action('plugins_loaded', 'woocommerce_gateway_parceladousa_init', 11);


function woocommerce_gateway_parceladousa_init(){
    // Checks with WooCommerce is installed.
    if (class_exists('WC_Payment_Gateway')) {

        /**
         * Includes Classes.
         */
        require_once plugin_dir_path(__FILE__) . '/includes/class-wc-gateway-parceladousa.php';
        require_once plugin_dir_path(__FILE__) . '/includes/class-wc-parceladousa-api.php';
    }

    /**
     * Load textdomain.
     */
    load_plugin_textdomain( 'woocommerce-gateway-parceladousa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_filter('woocommerce_payment_gateways', 'add_parceladousa_gateway');


/**
 * Add the gateway to WooCommerce.
 * @param  array $gateways WooCommerce payment methods.
 * @return array Payment methods with Parceladousa.
 */
function add_parceladousa_gateway($gateways){
    $gateways[] = 'WC_Gateway_Parceladousa';
    return $gateways;
}
