<?php

if (!defined('DESPACHALO_ADMIN')) {
	define('DESPACHALO_ADMIN', 'https://admin.despachalo.cl/');
}

if (!defined('DESPACHALO_API')) {
	define('DESPACHALO_API', 'https://admin.despachalo.cl/api-external/');
	//define('DESPACHALO_API', 'http://localhost:8080/api-external/');
}

if (!defined('TRACKING_URL')) {
	define('TRACKING_URL', 'https://livetracking.simpliroute.com/widget/account/30261/tracking/');
}

if (!session_id()) {
	session_start();
}

/**
 * despachalo_generar_etiqueta Acción que permite crear etiquetas desde la grilla de pedidos
 */
add_filter('bulk_actions-edit-shop_order', function ($bulk_actions) {
	$bulk_actions['despachalo_generar_etiqueta'] = __('Generar etiquetas Despachalo', 'txtdomain');
	return $bulk_actions;
});


/**
 * Ejecución de selecciones masivas desde grid de pedidos
 */
add_filter('handle_bulk_actions-edit-shop_order', function ($redirect_url, $action, $post_ids) {

	if ($action === 'despachalo_generar_etiqueta') {
		//Generar etiquetas Masivas
		$pedidos_etiqueta = []; // (array) Pedidos que ya posean etiqueta
		$ordenes_procesar = []; // Pedidos sin etiqueta

		foreach ($post_ids as $pid) {

			$tag = get_post_meta($pid, '_despachalo_etiquetas', true);

			if (!empty($tag)) {
				$pedidos_etiqueta[] = $pid;
			} else {
				array_push($ordenes_procesar, $pid);
			}
		}

		if (count($ordenes_procesar) > 0) {

			$respuesta = emitirEtiquetas($ordenes_procesar, 'multiple');
			$redirect_url = add_query_arg('despachalo_msg', $respuesta, $redirect_url);
		} else {
			$ordenes = implode(', ', $pedidos_etiqueta);
			$redirect_url = add_query_arg('despachalo_msg', "Atención, los pedidos {$ordenes} seleccionados ya poseen etiqueta, no es necesario volver a generar", $redirect_url);
		}
	} 

	return $redirect_url;
}, 10, 3);

// Aviso de éxito en pantalla al generar las etiquetas masivas o cancelarlas (o errores en su defecto) en formato alerta

add_action('admin_notices', 'despachalo_mensajes');


function despachalo_mensajes()
{
	if (isset($_GET['despachalo_msg'])) {
		$alertTxt = $_GET['despachalo_msg'];

		$html = "<div class='notice notice-success is-dismissible'>
			<p><strong>DESPACHALO</strong> | {$alertTxt}
			</p>
		</div>";

		print $html;
	}
}

// Agrega campo a la tabla de pedidos para marcar si el pedido tiene etiqueta o no, de tener etiqueta, mostrar boton para imprimirla directamente, si el pedido esta cancelado, mostrar Cancelado en rojo

add_filter('manage_edit-shop_order_columns', 'despachalo_columna_etiquetas');

function despachalo_columna_etiquetas($columns)
{
	$columns['etiqueta_despachalo'] = 'Etiqueta Despachalo';
	return $columns;
}

add_action('manage_shop_order_posts_custom_column', 'despachalo_agregar_comuna_etiqueta');

function despachalo_agregar_comuna_etiqueta($column)
{

	global $post;

	if ('etiqueta_despachalo' === $column) {

		$tag = get_post_meta($post->ID, '_despachalo_etiquetas', true);
		$cancelled = get_post_meta($post->ID, '_despachalo_fecha_cancelado');

		if (!empty($cancelled)) {
			echo "<p style='color:red;'>CANCELADO<p>";
		} else if (!empty($tag)) {

			$etiqueta_url = $tag;

?>
			<style>
				.despachalo-boton-etiqueta {
					text-align: center;
				}

				#despachalo-label {
					background-color: #120BD9;
					color: #fff;
					margin-bottom: 8px;
				}
			</style>

			<div class="despachalo-boton-etiqueta">
				<a id="despachalo-label" href="<?= $etiqueta_url ?>" target="_blank" class="button">Imprimir Etiqueta</a>
			</div>

		<?php
		} else {
			?>
			<style>
				.despachalo-boton-etiqueta {
					text-align: center;
				}

				.despachalo-ge-label-grid {
					background-color:  #fff;
					color: #120BD9;
					margin-bottom: 8px;
				}
			</style>
			<div class="despachalo-boton-etiqueta">
				<button onclick="crearEtiqueta('<?= $post->ID ?>');" id="despachalo-ge-label-grid-<?= $post->ID ?>" class="button despachalo-ge-label-grid">Generar Etiqueta</button>
			</div>
			<script type="text/javascript">
				function crearEtiqueta(id){
					var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
					jQuery.ajax({
						type: 'POST',
						cache: false,
						url: ajaxurl,
						data: {
							action: 'despachalo_generar_etiqueta',
							dataid: id,
						},
						success: function(data, textStatus, XMLHttpRequest) {
							location.reload();
						},
						error: function(MLHttpRequest, textStatus, errorThrown) {}
					});
				}
			</script>
		<?php
		}
	}
}

/**
 * Box genera etiquetas
 */
add_action('add_meta_boxes', 'wc_despachalo_shipping_box_add_box');

function wc_despachalo_shipping_box_add_box()
{
	add_meta_box('woocommerce-delivery-box', __('Despachalo', 'woocommerce-delivery'), 'wc_despachalo_shipping_box_create_box_content', 'shop_order', 'side', 'default');
}

function wc_despachalo_shipping_box_create_box_content()
{
	global $post;
	$site_url = get_site_url();

	$order = wc_get_order($post->ID);
	$shipping = $order->get_items('shipping');

	echo '<div class="despachalo-ge">';
	echo '<strong>Emitir e imprimir etiquetas</strong></br>';
	foreach ($shipping as $method) {
		echo $method['name'];
	}
	echo '</div>';

	//ETIQUETA
	$despachalo_tracking_number = get_post_meta($post->ID, '_tracking_number', true);
	$etiqueta = get_post_meta($post->ID, '_despachalo_etiquetas', true);

	if (!empty($despachalo_tracking_number)) {

		$site_tracking_url = TRACKING_URL.$despachalo_tracking_number;
		echo  '<div style="position: relative; width: 100%; height: 60px;"><a style=" width: 225px;text-align: center;background: #120BD9;color: white;padding: 10px;margin: 10px;float: left;text-decoration: none;" href="' . $etiqueta . '" target="_blank">IMPRIMIR ETIQUETA</a></div>';
		echo  '<div style="position: relative; width: 100%; height: 60px;" ><a style=" width: 225px; text-align: center;background: #120BD9;color: white;padding: 10px;margin: 10px;float: left;text-decoration: none;" href="' . $site_tracking_url . '" target="_blank">Seguir Paquete</a></div>';
		echo  '<div style="position: relative; width: 100%; margin-bottom:15px; " >Nro. Seguimiento: ' . $despachalo_tracking_number . '</div>';
	}


	if (empty($despachalo_tracking_number)) { ?>

		<style type="text/css">
			#despachalo-ge,
			#editar-despachalo,
			#manual-despachalo-generar {
				background: #120BD9;
				color: white;
				width: 100%;
				text-align: center;
				height: 40px;
				padding: 0px;
				line-height: 37px;
				margin-top: 20px;
				clear: both;
			}
		</style>

		<div id="despachalo-ge" class="button" data-id="<?php echo $post->ID; ?>">Generar Etiqueta</div>


		<div class="despachalo-ge-label"> </div>
		<script type="text/javascript">
			jQuery('body').on('click', '#despachalo-ge', function(e) {
				e.preventDefault();
				var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
				var dataid = jQuery(this).data("id");
				jQuery.ajax({
					type: 'POST',
					cache: false,
					url: ajaxurl,
					data: {
						action: 'despachalo_generar_etiqueta',
						dataid: dataid,
					},
					success: function(data, textStatus, XMLHttpRequest) {
						jQuery(".despachalo-ge-label").fadeIn(400);
						jQuery(".despachalo-ge-label").html('');
						jQuery(".despachalo-ge-label").append(data);
						console.log(data);
					},
					error: function(MLHttpRequest, textStatus, errorThrown) {}
				});
			});
		</script>
<?php }
}


add_action('wp_ajax_nopriv_despachalo_generar_etiqueta', 'despachalo_generar_etiqueta', 10);
add_action('wp_ajax_despachalo_generar_etiqueta', 'despachalo_generar_etiqueta', 10);


/* GENERAR ETIQUETA */
function despachalo_generar_etiqueta()
{
	global $woocommerce, $post;
	$order = array($_POST['dataid']);
	$etiqueta = emitirEtiquetas($order, 'simple');
	$tracking = TRACKING_URL.$etiqueta['data'][0]->tracking;

	if($etiqueta['status'] == 201){
		?>
		<script type="text/javascript">
			location.reload();
		</script>
		<?php
	}else{
		$html = '<p>Error al generar la etiqueta</p>';
	}

	return PRINT $html;
}

/**
 * Funcion para emitir etiquetas
 *
 * @param array $ordenes Array de ids de ordenes
 * @param string $tipo Tipo de etiqueta a emitir simple o multiple
 * @return void
 */
function emitirEtiquetas($ordenes, $tipo)
{
	$totalOrdenes = count($ordenes);
	$errores = []; //array de ids de ordenes con errores
	$completadas = []; //array de ids de ordenes completadas

	foreach ($ordenes as $orderId) {
		$order = wc_get_order($orderId);
		$productos = $order->get_items();
		$items = array();

		foreach ($productos as $p => $producto) {
			$product = wc_get_product($producto->get_product_id());

			$items[] = array(
				"identifier" => $product->get_sku(),
				"name" => $producto->get_name(),
				"qty" => $producto->get_quantity(),
				"link" => get_site_url() . "/products/{$product->get_slug()}",
				"price" => $producto->get_total(),
			);
		}

		$arrayData = array(
			"reference_number" => $orderId,
			"customer_email" => $order->billing_email,
			"customer_phone" => $order->billing_phone,
			"customer_identifier" => "", //QUE VALOR SE PASA? YA QUE EL RUT NO SABREMOS QUE DATO ES
			"customer_name" => "{$order->billing_first_name} {$order->billing_last_name}",
			"tags" => "woocommerce",
			"address" => array(
				"region" => "Region Metropolitana",
				//"region" => $order->shipping_state, //valor CL_RM
				"city" => $order->shipping_city,
				"full_address" => "{$order->shipping_address_1} {$order->shipping_address_2}",
				"latlong" => "", //QUE VALOR SE PASA?
				"type_adress" => "", //QUE VALOR SE PASA?
				"notes" => $order->customer_note
			),
			"order" => array(
				"size_packages" => "M", //QUE VALOR SE PASA?
				"amount_secure" => $order->total,
				"items" => $items
			),
		);
		$etiqueta = crearDespacho($arrayData);

		if ($etiqueta['status'] == 201) {
			//Se ha creado el despacho
			update_post_meta($orderId, '_tracking_number', $etiqueta['data'][0]->tracking_number);
			update_post_meta($orderId, '_despachalo_etiquetas', $etiqueta['data'][0]->label);
			$completadas[] = $orderId;
		} else {
			//No se ha creado el despacho
			$errores[] = $orderId;
		}
	}

	if ($tipo == 'multiple') {
		if ($totalOrdenes == count($completadas)) {
			//Todas las ordenes se completaron
			$mensaje = "Se han completado todas las ordenes de forma exitosa";
		} else {
			//No se completaron todas las ordenes
			$completo = implode(",", $completadas);
			$error = implode(",", $errores);
			$mensaje = "Las siguientes ordenes se generaron de forma exitosa ({$completo}) y las siguientes no se pudieron generar ({$error})";
		}
		return $mensaje;
	}else{
		return $etiqueta;
	}
}

function crearDespacho($arrayData)
{
	$token = getToken();

	if (!$token || empty($token)) {
		return "No se encontro el token para usos de servicios despachalo";
	}

	$authorization = "Authorization: Bearer $token";
	$url = DESPACHALO_API.'delivery';
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => json_encode($arrayData),
		CURLOPT_HTTPHEADER => array(
			'Cache-Control: no-cache',
			'Content-Type: application/json',
			$authorization
		)
	));

	$response = curl_exec($curl);
	$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
	return array(
		"status" => $httpcode,
		"data" => json_decode($response)
	);
}

/**
 * Funcion para obtener token de las variables configuradas en la zona del shipping despachalo
 *
 * @return string
 */
function getToken()
{
	$delivery_zones = WC_Shipping_Zones::get_zones();
	foreach ($delivery_zones as $zones) {
		foreach ($zones['shipping_methods'] as $methods) {
			if ($methods->id == 'despachalo_rest') {
				if ($methods->enabled == 'yes') {
					return $methods->instance_settings['token'];
				}
			}
		}
	}
}
