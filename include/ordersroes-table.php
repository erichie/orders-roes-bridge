<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ); // since WP 3.1.
}

class OrdersRoes_Table extends WP_List_Table {
	
	function get_columns()
	{
		$columns = array(
			'name' => 'Order Name',
			'status' => 'Order Status',
			'customer'   => 'Customer',
			'date' => 'Date'
		);
		return $columns;
	}

	function column_default($item, $column_name)
	{
		return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
	}

	function prepare_items($orders)
	{
		$columns = $this->get_columns();
		$this->_column_headers = array($columns, array(), array());
		$this->items = $orders;
	}
}