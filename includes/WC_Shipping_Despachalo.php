<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!session_id()) {
	session_start();
}

/**
 * WC_Shipping_Despachalo class.
 *
 * @extends WC_Shipping_Method
 */
class WC_Shipping_Despachalo extends WC_Shipping_Method
{
	private $default_boxes;
	private $found_rates;

	/**
	 * Constructor
	 */
	public function __construct($instance_id = 0)
	{

		$this->id                   = 'despachalo_rest';
		$this->instance_id 			= absint($instance_id);
		$this->method_title         = __('Despachalo', 'woocommerce-shipping-despachalo');
		$this->method_description   = __('Metodo Shipping Despachalo.', 'woocommerce');
		$this->supports             = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init();

		add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	 * init function.
	 */
	public function init()
	{
		// Load the settings.
		$this->init_form_fields = include('data/settings.php');
		$this->init_settings();
		$this->instance_form_fields = include('data/settings.php');

		// Define user set variables
		$this->title = $this->get_option('title', $this->method_title);
		$this->costo_envio = $this->get_option('costo_envio');
		$this->envios_gratis_activado = $this->get_option('envios_gratis_activado');
		$this->envios_gratis   	= $this->get_option('envios_gratis');
		$this->cobertura_por_api	= $this->get_option('cobertura_por_api');

		$this->token = $this->get_option('token');
	}

	/**
	 * admin_options function.
	 */
	public function admin_options()
	{
		// Show settings
		parent::admin_options();
	}

	/**
	 * calculate_shipping function.
	 *
	 * @param mixed $package
	 */
	public function calculate_shipping($package = array())
	{
		$rate = array(
			'id' => sprintf("%s", $this->id),
			'label' => sprintf("%s", $this->title),
			'calc_tax' => 'per_item',
			'package' => $package,
		);

		if ($this->cobertura_por_api == 'yes') { // Buscamos precio de tarifa por API

			$destino = $package['destination'];

			$cobertura = $this->getCobertura($destino['city']);

			if ($cobertura['resultado'] !== true) {
				return; // Desactivamos el metodo por no tener cobertura
			}
		}
		if ($this->envios_gratis_activado === 'yes' && $package['contents_cost'] >= $this->envios_gratis) {
			$rate['cost'] = 0;
		}else{
			$rate['cost'] = $this->costo_envio;
		}
		

		$this->add_rate($rate);
	}

	public function getCobertura($city)
	{
		$url = "http://localhost:8080";
		$endpoint = "{$url}/api-external/cobertura";
		$headerBearer = "Authorization: Bearer {$this->token}";
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $endpoint,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_POSTFIELDS => '{ "comuna": "'.$city.'" }',
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			$headerBearer
		  ),
		));
		
		$response = curl_exec($curl);
		$cobertura = json_decode($response, true);

		curl_close($curl);

		return $cobertura;
	}

	/**
	 * sort_rates function.
	 **/
	public function sort_rates($a, $b)
	{
		if ($a['sort'] == $b['sort']) return 0;
		return ($a['sort'] < $b['sort']) ? -1 : 1;
	}
}
