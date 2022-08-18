<?php

/**
 * WooCommerce Camisa Dimona
 *
 * @package           CamisaDimona
 * @author            Luiz Felipe Vicente da Costa
 * @copyright         2022 Luiz Felipe Vicente da Costa
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Camisa Dimona
 * Plugin URI:        https://github.com/lfvicent3/camisa-dimona
 * Description:       Camisa Dimona Dropshipping Integration
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Luiz Felipe Vicente da Costa
 * Author URI:        https://github.com/lfvicent3
 * Text Domain:       moda-academica
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action('woocommerce_shipping_init', 'dimona_shipping_init');

function dimona_shipping_init()
{
    if (!class_exists('DimonaShipping')) {
        class DimonaShipping extends WC_Shipping_Method
        {
            protected $apikey = '';

            public function __construct($instance_id = 0)
            {
                $this->id = 'dimona-shipping';
                $this->instance_id = absint($instance_id);
                $this->method_title = __('Camisa Dimona Shipping');
                $this->method_description = __('Calculo de frete por meio da API da Camisa Dimona');
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                );
                $this->init_form_fields();
                $this->init_settings();

                $this->instance_form_fields = array(
                    'apikey' => array(
                        'title' => __('ApiKey'),
                        'type' => 'text',
                        'description' => __('API KEY da camisa dimona, obtido em camisadimona.com.br'),
                        'default' => __(''),
                        'desc_tip' => true
                    )
                );

                $this->enabled = $this->get_option('enabled');
                $this->title = 'Camisa Dimona';
                $this->apikey = $this->get_option('apikey');

                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            public function calculate_shipping($package = array())
            {
                $args = array(
                    'body'        => json_encode(array(
						'zipcode' => $package['destination']['postcode'], 
						'quantity' => count($package['contents']),
					)),
                    'timeout'     => '10',
                    'redirection' => '10',
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => array(
                        'api-key' => $this->apikey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ),
                );

                $response = wp_remote_post('https://camisadimona.com.br/api/v2/shipping', $args);
				
                foreach (json_decode($response['body'], true) as $method) {
                    $this->add_rate(array(
                        'id'    => $this->id . $this->instance_id . '-' . $method['delivery_method_id'],
                        'label' => $method['name'],
                        'cost'  => $method['value'],
                        //'cost_tax' => 'per_item'
                    ));
                }
            }
        }
    }
}

add_filter('woocommerce_shipping_methods', 'add_dimona_method');

function add_dimona_method($methods)
{
    $methods['dimona-shipping'] = 'DimonaShipping';
    return $methods;
}
