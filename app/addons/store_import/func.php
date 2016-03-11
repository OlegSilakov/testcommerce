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

use Tygh\Logger;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_store_import_after_set_progress($prop, $value, $extra)
{
    if (defined('STORE_IMPORT') && ($prop == 'echo' || $prop == 'title')) {
        $value = strip_tags($value);
        Logger::instance()->write($value);
    }
}
