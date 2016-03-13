<?php
if ($mode == 'manage') {
	$orders_history = fn_get_all_history();
	Tygh::$app['view']->assign('orders_history', $orders_history);
}
?>