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

class OrdersRoes {

	protected static $instance = null;

	public function __construct()
	{
		register_activation_hook( __FILE__, array(&$this, 'activate'));
		register_deactivation_hook( __FILE__, array(&$this, 'deactivate'));
		add_action('init', array( &$this, 'init'));
		add_action('admin_menu', array(&$this, 'add_menu'));
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
		include(dirname( __FILE__ ) . '/manage.php');
	}

	public static function get_orders()
	{
		$orders_with_meta = array();
		$args = array(
			'post_type' => 'sunshine-order'
		);

		$orders = get_posts($args);
		foreach ($orders as $order) {
			$order_meta = get_post_meta($order->ID);
			$order->order_meta = $order_meta;
			$sunshine_order_data = unserialize(unserialize($order->order_meta['_sunshine_order_data'][0]));
			$sunshine_order_items = unserialize(unserialize($order->order_meta['_sunshine_order_items'][0]));
			$order->order_meta['data'] = $sunshine_order_data;
			$order->order_meta['items'] = $sunshine_order_items;
			unset($order->order_meta['_sunshine_order_data']);
			unset($order->order_meta['_sunshine_order_items']);
			$orders_with_meta[] = $order;
		}
		//die(var_dump($orders_with_meta[0]->order_meta));
		return $orders_with_meta;
	}
}

$GLOBALS['orders_roes'] = OrdersRoes::get_instance();