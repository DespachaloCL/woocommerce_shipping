<?php
if (!defined('ABSPATH')) {
	exit;
}
$callback = get_site_url();

/**
 * Array of settings
 */
return array(
	'enabled'           => array(
		'title'           => __('Activar Despachalo', 'woocommerce-shipping-despachalo'),
		'type'            => 'checkbox',
		'label'           => __('Activar este método de envió', 'woocommerce-shipping-despachalo'),
		'default'         => 'no'
	),

	'title'             => array(
		'title'           => __('Título', 'woocommerce-shipping-despachalo'),
		'type'            => 'text',
		'description'     => __('Controla el título que el usuario ve durante el pago.', 'woocommerce-shipping-despachalo'),
		'default'         => __('Despachalo', 'woocommerce-shipping-despachalo'),
		'desc_tip'        => true
	),

	'token'	=> array(
		'title'			=> __('Token', 'woocommerce-shipping-despachalo'),
		'type'          => 'text',
		'description'   => __('Token proporcionada por Despachalo', 'woocommerce-shipping-despachalo'),
		'default'       => __('', 'woocommerce-shipping-despachalo'),
		'placeholder' 	=> __('', 'meta-box'),
	),

	'cobertura_por_api'	=> array(
		'title'			=> __('Buscar cobertura por API (recomendado)', 'woocommerce-shipping-despachalo'),
		'type'          => 'checkbox',
		'label'         => __('Esta opcion valida la cobertura de comunas disponible de despachalo', 'woocommerce-shipping-despachalo'),
		'default'       => __('yes', 'woocommerce-shipping-despachalo'),
		'placeholder' 	=> __('', 'meta-box'),
	),

	'costo_envio'		=> array(
		'title'         => __('Valor del Envio', 'woocommerce-shipping-despachalo'),
		'type'          => 'text',
		'description'   => __('Costo fijo del envío.', 'woocommerce-shipping-despachalo'),
		'default'       => __('', 'woocommerce-shipping-despachalo'),
		'placeholder' 	=> __('1', 'meta-box'),
	),

	'envios_gratis_activado' => array(
		'title'           => __('Activar envíos gratis', 'woocommerce-shipping-despachalo'),
		'type'            => 'checkbox',
		'label'           => __('Si activa esta opción permite dar costo de envíos gratis a los clientes', 'woocommerce-shipping-despachalo'),
		'default'         => 'no'
	),

	'envios_gratis'    => array(
		'title'           => __('Envio Gratis a partir de', 'woocommerce-shipping-despachalo'),
		'type'            => 'text',
		'description'     => __('Envios gratis para montos mayores:', 'woocommerce-shipping-despachalo'),
		'default'         => __('', 'woocommerce-shipping-despachalo'),
		'placeholder' => __('1', 'meta-box'),
	)

);
