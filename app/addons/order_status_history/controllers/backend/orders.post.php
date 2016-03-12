<?php

if (!defined('BOOTSTRAP')) {
	die('Access denied');
}

if ($mode == 'update_status') {
	$order_info = fn_get_order_short_info($_REQUEST['id']);
	$timestamp = time();
	$user_id = $_SESSION['auth']['user_id'];
	// if ($order_info['status'] == )
	$history_item = fn_save_status_change ($_REQUEST['id'], $old_status, $order_info['status'], $user_id, $timestamp);
}

?>