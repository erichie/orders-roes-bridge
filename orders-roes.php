<?php
/*
Plugin name: Orders ROES Bridge
Version: 1.0
Author: Edward Richards
Description: Send Sunshine Photocart Orders through ROES Bridge
Text Domain: orders-roes-bridge
*/

if ( ! function_exists( 'add_action' ) ) {
	exit();
}

define( 'ORDERSROES_DIR', dirname( __FILE__ ) ); // our directory.
define( 'ORDERSROES_INC', ORDERSROES_DIR . '/include' );
define( 'ORDERSROES_ADMIN',  ORDERSROES_DIR . '/admin' );

class OrdersRoes {

	protected static $instance = null;

	public function __construct()
	{
		register_activation_hook( __FILE__, array(&$this, 'activate'));
		register_deactivation_hook( __FILE__, array(&$this, 'deactivate'));
		add_action('init', array( &$this, 'init'));
		add_action('admin_menu', array(&$this, 'add_menu'));
		spl_autoload_register(array(&$this, 'autoload'));
	}

	public function activate()
	{
		//
	}

	public function deactivate()
	{
		//
	}

	public function init()
	{
		register_taxonomy('orderroes_status', 'sunshine-order');
	}

	public function autoload($class)
	{
		// not an OrderRoes_ class.
		if (0 !== strncmp('OrdersRoes_', $class, 9)) {
			return;
		}

		$class = self::convert_class_to_file($class);
		foreach (array(ORDERSROES_INC, ORDERSROES_ADMIN ) as $path) {
			if (file_exists($file = "$path/$class.php")) {
				require_once($file);
				break;
			}
		}
	}

	public static function convert_class_to_file($class)
	{
		return str_replace( '_', '-', strtolower($class));
	}

	public static function get_instance()
	{
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function add_menu()
	{
		add_menu_page(
			'ROES Orders',
			'ROES Orders',
			'manage_options',
			'ordersroes',
			array($this, 'display_manage_page'),
			'dashicons-layout',
			99
		);
	}

	public function display_manage_page()
	{
		include(ORDERSROES_ADMIN . '/manage.php');
	}

	public static function get_orders()
	{
		$filtered_orders = array();
		$args = array(
			'post_type' => 'sunshine-order'
		);

		$orders = get_posts($args);
		foreach ($orders as $order) {
			$filtered_order = array();
			$order_data = maybe_unserialize(get_post_meta($order->ID, '_sunshine_order_data', true));
			$order_items = maybe_unserialize(get_post_meta($order->ID, '_sunshine_order_items', true));
			// wp_set_post_terms($order->ID, array('name' => 'Complete'), 'orderroes_status');

			if (empty(wp_get_post_terms($order->ID, 'orderroes_status'))) {
				wp_set_post_terms($order->ID, array('name' => 'Pending'), 'orderroes_status');
				$order_status = wp_get_post_terms($order->ID, 'orderroes_status');
			}
			else {
				$order_status = wp_get_post_terms($order->ID, 'orderroes_status');
			}

			$filtered_order = array(
				'id' => $order->ID,
				'name' => $order->post_title,
				'date' => $order->post_date,
				'status' => $order_status[0]->name,
				'customer' => $order_data['first_name'] . ' ' . $order_data['last_name']
			);

			$filtered_orders[] = $filtered_order;
		}

		return $filtered_orders;
	}

	public static function send_orders()
	{
		$args = array(
			'post_type' => 'sunshine-order'
		);
		$orders = get_posts($args);

		foreach ($orders as $order) {
			$order_data = maybe_unserialize(get_post_meta($order->ID, '_sunshine_order_data', true));
			$order_items = maybe_unserialize(get_post_meta($order->ID, '_sunshine_order_items', true));

			$order_status = wp_get_post_terms($order->ID, 'orderroes_status');

			if (!empty($order_status) && $order_status[0]->slug == 'pending') {
				$order_xml = new SimpleXMLElement("<order></order>");
				$order_xml->addAttribute('OrderNumber', $order->ID);
				$order_xml->addAttribute('customernumber', $order_data['user_id']);
				$order_xml->addAttribute('orbvendorid', 'test');
				$order_xml->addAttribute('orbvendorpassword', 'test');
				$order_xml->addAttribute('labid', 'test');
				$order_xml->addAttribute('producttotal', $order_data['subtotal']);
				$order_xml->addAttribute('shippingtotal', $order_data['shipping_cost']);
				$order_xml->addAttribute('taxtotal', $order_data['tax']);
				$order_xml->addAttribute('totalprice', $order_data['total']);

				$customer_xml = $order_xml->addChild('customer');
				$customer_xml->addChild('firstname', $order_data['first_name']);
				$customer_xml->addChild('lastname', $order_data['last_name']);
				$customer_xml->addChild('email', $order_data['email']);

				$shipping_xml = $order_xml->addChild('shippingaddress');
				$shipping_xml->addChild('firstname', $order_data['shipping_first_name']);
				$shipping_xml->addChild('lastname', $order_data['shipping_last_name']);
				$shipping_xml->addChild('address1', $order_data['shipping_address']);
				$shipping_xml->addChild('address2', $order_data['shipping_address2']);
				$shipping_xml->addChild('city', $order_data['shipping_city']);
				$shipping_xml->addChild('state', $order_data['shipping_state']);
				$shipping_xml->addChild('zip', $order_data['shipping_zip']);
				$shipping_xml->addChild('countrycode', $order_data['shipping_country']);
				$shipping_xml->addChild('phone', $order_data['phone']);
				$shipping_xml->addChild('email', $order_data['email']);
				$order_xml->addChild('shippingmethod', $order_data['shipping_method']);

				$item_count = 1;
				foreach ($order_items as $item) {
					$image_xml = $order_xml->addChild('image');
					$image_xml->addAttribute('id', $item['image_id']);
					$image_url = wp_get_attachment_url($item['image_id']);
					$image_meta = wp_get_attachment_metadata($item['image_id']);
					$image_xml->addAttribute('url', $image_url);
					$image_xml->addAttribute('height', $image_meta['height']);
					$image_xml->addAttribute('width', $image_meta['width']);
					$image_xml->addAttribute('filename', $item['image_name']);

					$item_xml = $order_xml->addChild('item');
					$item_xml->addAttribute('id', $item_count);
					$item_xml->addAttribute('quantity', $item['qty']);
					$item_xml->addAttribute('totalprice', $item['total']);
					$item_xml->addAttribute('price', $item['price']);

					$product = get_post($item['product_id']);
					$test = get_post_meta($product->ID);
					// die(var_dump($product));
				}

				// die(var_dump($order_xml->asXML()));
				// die(var_dump($order_xml));
				// wp_set_post_terms($order->ID, array('name' => 'Complete'), 'orderroes_status');
			}
			// else if (!empty($order_status) && $order_status[0]->slug == 'complete') {
			// 	wp_set_post_terms($order->ID, array('name' => 'Pending'), 'orderroes_status');
			// }
		}
	}
}

$GLOBALS['orders_roes'] = OrdersRoes::get_instance();

// save this for when we actually need post meta
// $order_meta = get_post_meta($order->ID);
// $order->order_meta = $order_meta;
// $sunshine_order_data = unserialize(unserialize($order->order_meta['_sunshine_order_data'][0]));
// $sunshine_order_items = unserialize(unserialize($order->order_meta['_sunshine_order_items'][0]));
// $order->order_meta['data'] = $sunshine_order_data;
// $order->order_meta['items'] = $sunshine_order_items;
// unset($order->order_meta['_sunshine_order_data']);
// unset($order->order_meta['_sunshine_order_items']);
// $orders_with_meta[] = $order;