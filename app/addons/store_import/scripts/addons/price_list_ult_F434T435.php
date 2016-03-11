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

$section = db_get_row("SELECT section_id FROM ?:settings_sections WHERE name = 'price_list'");

if (!empty($section)) {
    $tab_id = '';
    $settings = array('table_layout_header', 'disable_images_in_xml', 'xls_header', 'xls_url', 'pdf_header', 'pdf_url', 'clear_header', 'clear_url');
    foreach ($settings as $option_name) {
        $option = db_get_row("SELECT object_id, section_tab_id FROM ?:settings_objects WHERE name = '" . $option_name . "' AND section_id = " . $section['section_id']);
        if (!empty($option)) {
            if (empty($tab_id)) {
                $tab_id = $option['section_tab_id'];
            }
            db_query("DELETE FROM ?:settings_objects WHERE object_id = " . $option['object_id']);
            db_query("DELETE FROM ?:settings_descriptions WHERE object_type = 'O' AND object_id = " . $option['object_id']);
        }
    }

    if (!empty($tab_id)) {
        db_query("INSERT INTO ?:settings_objects (`edition_type`, `name`, `section_id`, `section_tab_id`, `type`, `value`, `position`, `is_global`, `handler`, `parent_id`) VALUES
                    ('ROOT,ULT:VENDOR', 'price_list_info', {$section['section_id']}, {$tab_id}, 'O', '', 0, 'N', 'fn_price_list_info', 0)");
    }
}
