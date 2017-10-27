<?php

$orders = OrdersRoes::get_orders();
// die(var_dump($orders));

?>

<h1>Orders</h1>

<?php

$orders_table = new OrdersRoes_Table();
$orders_table->prepare_items($orders);
$orders_table->display();

?>