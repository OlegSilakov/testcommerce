<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_save_status_change ($order_id, $old_status, $new_status, $user_id, $timestamp) {
	$data = array(
		'order_id' => $order_id,
		'old_status' => $old_status,
		'new_status' => $new_status,
		'user_id' => $user_id,
		'timestamp' => $timestamp,
	);
	$history_item = db_query("INSERT INTO ?:order_status_history ?e", $_data);
	return $history_item
}
?>