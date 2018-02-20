<?php

if (isset($_GET['oroesaction']) && $_GET['oroesaction'] == 'send-orders') {
	OrdersRoes::send_orders();
}

?>

<h1>Orders</h1>

<?php

$orders_table = new OrdersRoes_Table();
$orders_table->prepare_items();
$orders_table->display();

printf(
	'<a href="%s" class="button button-primary">%s</a>',
	admin_url('admin.php?page=ordersroes&oroesaction=send-orders'),
	__('Send Pending Orders', 'lingotek-translation')
);

?>


