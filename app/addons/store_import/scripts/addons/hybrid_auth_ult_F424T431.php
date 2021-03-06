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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

db_query("REPLACE INTO ?:privileges (privilege, is_default, section_id) VALUES ('manage_providers', 'Y', 'addons')");
db_query("REPLACE INTO ?:privileges (privilege, is_default, section_id) VALUES ('view_providers', 'Y', 'addons')");

db_query("ALTER TABLE ?:hybrid_auth_providers CHANGE `app_params` `app_params` text");
