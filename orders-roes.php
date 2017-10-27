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
		//
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
			$order_meta = get_post_meta($order->ID);
			$order->order_meta = $order_meta;
			$order_data = unserialize(unserialize($order->order_meta['_sunshine_order_data'][0]));

			$filtered_order = array(
				'id' => $order->ID,
				'name' => $order->post_title,
				'date' => $order->post_date,
				'customer' => $order_data['first_name'] . ' ' . $order_data['last_name']
			);

			$filtered_orders[] = $filtered_order;
		}
		// die(var_dump($filtered_orders));
		return $filtered_orders;
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