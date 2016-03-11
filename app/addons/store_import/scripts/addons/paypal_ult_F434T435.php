<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\StoreImport;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$new_template = 'addons/paypal/views/orders/components/payments/paypal_express.tpl';

$pp_express_processor_id = db_get_row(
    "SELECT processor_id"
    . " FROM ?:payment_processors"
    . " WHERE processor_script = 'paypal_express.php'"
);

if ($pp_express_processor_id) {
    $pp_express_processor_id = $pp_express_processor_id['processor_id'];

    // update processor template
    db_query(
        "UPDATE ?:payment_processors"
        . " SET processor_template = '{$new_template}'"
        . " WHERE processor_id = {$pp_express_processor_id}"
    );

    // update payments templates
    db_query(
        "UPDATE ?:payments"
        . " SET template = '{$new_template}'"
        . " WHERE processor_id = {$pp_express_processor_id}"
    );
}
