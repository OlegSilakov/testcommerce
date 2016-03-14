<?php
if ($mode == 'manage') {
	$orders_history = fn_get_all_history();
	$users = fn_get_short_user();
	Tygh::$app['view']->assign('orders_history', array_reverse($orders_history));
	Tygh::$app['view']->assign('users', $users);
}
?>