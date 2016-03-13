<?php

$schema['order_status_history'] = array (
    'permissions' => 'manage_order_status_history',
);

$schema['tools']['modes']['update_status']['param_permissions']['table']['order_status_history'] = 'manage_order_status_history';

return $schema;