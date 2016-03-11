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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$addon_status = db_get_row("SELECT * FROM ?:addons WHERE addon = 'store_locator'");
if (!empty($addon_status)) {
    db_query(
        "INSERT INTO ?:privileges SET privilege = 'manage_store_locator', is_default = 'Y', section_id = 'addons'"
    );
    db_query(
        "INSERT INTO ?:privileges SET privilege = 'view_store_locator', is_default = 'Y', section_id = 'addons'"
    );
}
