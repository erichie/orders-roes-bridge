<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ); // since WP 3.1.
}

class OrdersRoes_Table extends WP_List_Table {
	
	function get_columns()
	{
		$columns = array(
			'name' => 'Order Name',
			'date' => 'Date',
			'customer'   => 'Customer'
		);
		return $columns;
	}

	function column_default($item, $column_name)
	{
		switch($column_name) {
			case 'name':
			case 'date':
			case 'customer':
				return $item[$column_name];
			default:
				return print_r($item, true);
		}
	}

	function prepare_items($orders)
	{
		$columns = $this->get_columns();
		$this->_column_headers = array($columns, array(), array());
		$this->items = $orders;
	}
}