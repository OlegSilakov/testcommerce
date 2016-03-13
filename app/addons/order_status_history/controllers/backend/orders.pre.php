<?php

if (!defined('BOOTSTRAP')) {
	die('Access denied');
}

if ($mode == 'update_status') {
	if (is_null($_REQUEST['id']) || is_null($_REQUEST['status'])) return;
	$order_info = fn_get_order_short_info($_REQUEST['id']);
	$timestamp = time();
	$user_id = $_SESSION['auth']['user_id'];

	$history_item = fn_save_status_change ($_REQUEST['id'], $order_info['status'], $_REQUEST['status'], $user_id, $timestamp);
}

?>