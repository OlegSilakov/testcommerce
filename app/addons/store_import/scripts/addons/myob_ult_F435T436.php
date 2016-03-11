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

$myob_installed = db_get_row(
    "SELECT addon"
    . " FROM ?:addons"
    . " WHERE addon = 'myob'"
);

if ($myob_installed) {
    $setting_section = db_get_row(
        "SELECT section_id"
        . " FROM ?:settings_sections"
        . " WHERE name = 'myob'"
        . " AND type = 'ADDON'"
    );

    if ($setting_section) {
        $section_id = $setting_section['section_id'];

        $section_tab = db_get_row(
            "SELECT section_id"
            . " FROM ?:settings_sections"
            . " WHERE parent_id = '{$section_id}'"
            . " AND type = 'TAB'"
        );

        if ($section_tab) {
            $tab_id = $section_tab['section_id'];

            $already_updated = db_get_row(
                "SELECT object_id"
                . " FROM ?:settings_objects"
                . " WHERE section_id = {$section_id}"
                . " AND section_tab_id = {$tab_id}"
                . " AND name = 'tax_gst_code'"
            );

            if (!$already_updated) {
                $position = 10;

                $settings = array(
                    'currency' => array(
                        'type' => 'S',
                        'value' => 'AUD',
                    ),
                    'tax_gst_code' => array(
                        'type' => 'I',
                        'value' => 'GST',
                    ),
                    'freight_tax_gst_code' => array(
                        'type' => 'I',
                        'value' => 'GST',
                    )
                );

                foreach ($settings as $name => $setting) {
                    db_query(
                        "INSERT INTO ?:settings_objects"
                        . " (name, section_id, section_tab_id, type, value, position, is_global)"
                        . " VALUES ('{$name}', {$section_id}, {$tab_id}, '{$setting['type']}', '{$setting['value']}', {$position}, 'N')"
                    );
                    $position += 10;
                }
            }
        }
    }
}
