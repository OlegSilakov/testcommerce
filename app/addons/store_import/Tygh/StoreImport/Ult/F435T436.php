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

namespace Tygh\StoreImport\Ult;

use Tygh\StoreImport\General;
use Tygh\StoreImport\Table;
use Tygh\Registry;

class F435T436
{
    protected $store_data = array();
    protected $main_sql_filename = 'ult_F435T436.sql';

    protected $sections = array(
        2 => array(
            'section_id' => 2,
            'edition_type' => 'ROOT,VENDOR',
            'name' => 'General',
            'type' => 'CORE',
            'position' => 10,
        )
    );
    protected $settings = array(
        'enable_compare_products' => array(
            'object_id' => 307,
            'edition_type' => 'ROOT,ULT:VENDOR',
            'name' => 'enable_compare_products',
            'section_id' => 2,
            'section_tab_id' => 0,
            'type' => 'C',
            'value' => 'Y',
            'position' => 130,
            'is_global' => 'Y',
            'description' => 'Allow customers to compare products',
        )
    );

    private static $object_id = null;

    public function __construct($store_data)
    {
        $store_data['product_edition'] = 'ULTIMATE';
        $this->store_data = $store_data;
    }

    public function import($db_already_cloned)
    {
        General::setProgressTitle(__CLASS__);
        if (!$db_already_cloned) {
            if (!General::cloneImportedDB($this->store_data)) {
                return false;
            }
        } else {
            General::setEmptyProgressBar(__('importing_data'));
            General::setEmptyProgressBar(__('importing_data'));
        }

        General::connectToOriginalDB(array('table_prefix' => General::formatPrefix()));
        General::processAddons($this->store_data, __CLASS__);

        $main_sql = Registry::get('config.dir.addons') . 'store_import/database/' . $this->main_sql_filename;
        if (is_file($main_sql)) {
            //Process main sql
            if (!db_import_sql_file($main_sql)) {
                return false;
            }
        }

        $this->add_compare_list_setting();

        $this->ult_update_enable_compare_products_setting();

        $this->drop_feature_comparison_column();

        $this->add_shipping_description();

        $this->pages_drop_related_ids_field();

        $this->alter_product_weight_precision();

        $this->add_indexes();

        $this->change_file_size_type();

        $this->change_status_tables_linking();

        $this->update_setting_timezone();

        General::setActualLangValues();
        General::updateAltLanguages('language_values', 'name');
        General::updateAltLanguages('ult_language_values', array('name', 'company_id'));
        General::updateAltLanguages('settings_descriptions', array('object_id', 'object_type'));

        General::setEmptyProgressBar();
        General::setEmptyProgressBar();
        General::setEmptyProgressBar();
        General::setEmptyProgressBar();

        return true;
    }

    protected function add_compare_list_setting()
    {
        foreach ($this->settings as $item) {
            $this->createSetting($item);
        }

    }

    protected function ult_update_enable_compare_products_setting()
    {

        $table_product = new Table(\Tygh::$app['db'], "?:products");

        if (!$table_product->hasColumn('feature_comparison')) {
            return;
        }

        $row = db_get_row("SELECT object_id FROM ?:settings_objects WHERE name = 'enable_compare_products'");
        $object_id = $row['object_id'];

        $companies = db_get_array("SELECT company_id FROM ?:companies");

        foreach ($companies as $company) {
            $enabled = true;
            $result = db_get_row("SELECT product_id FROM ?:products WHERE company_id = {$company['company_id']} AND feature_comparison = 'Y' LIMIT 1");

            if (empty($result)) {
                if (count($companies) > 1) {
                    $sql = <<<SQL
    SELECT p.product_id FROM ?:products p
      INNER JOIN ?:products_categories pc ON pc.product_id = p.product_id
      INNER JOIN ?:categories c ON c.category_id = pc.category_id
    WHERE p.feature_comparison = 'Y' AND c.company_id = {$company['company_id']}
    LIMIT 1
SQL;
                    $result = db_get_row($sql);

                    if (empty($result)) {
                        $enabled = false;
                    }
                } else {
                    $enabled = false;
                }
            }

            if (!$enabled) {
                if (count($companies) > 1) {
                    db_query("INSERT INTO ?:settings_vendor_values (object_id, company_id, value) VALUES ({$object_id}, {$company['company_id']}, 'N')");
                } else {
                    db_query("UPDATE ?:settings_objects SET value = 'N' WHERE object_id = {$object_id}");
                }
            }
        }

    }

    protected function drop_feature_comparison_column()
    {

        $table_product = new Table(\Tygh::$app['db'], "?:products");

        if (!$table_product->hasColumn('feature_comparison')) {
            return;
        }

        db_query("ALTER TABLE ?:products DROP COLUMN feature_comparison");

    }

    protected function add_shipping_description()
    {

        $shipping_descriptions = new Table(\Tygh::$app['db'], "?:shipping_descriptions");
        if (!$shipping_descriptions->hasColumn('description')) {
            // fixes encoding issue
            db_query("SET NAMES UTF8");

            // add column
            $shipping_descriptions
                ->addColumn('description', 'string', array('limit' => 65536))
                ->update();

            $languages = db_get_array("SELECT lang_code  FROM ?:languages");

            // add descriptions
            foreach ($languages as $lang) {
                $description_parts = db_get_array(
                    "SELECT value FROM ?:language_values"
                    . " WHERE name IN ('delivery_times_text', 'shipping_tips')"
                    . " AND lang_code = '{$lang['lang_code']}'"
                    . " ORDER BY name ASC"
                );

                $localized_description = '';

                foreach ($description_parts as $part) {
                    $localized_description .= addslashes($part['value']);
                }

                db_query(
                    "UPDATE ?:shipping_descriptions"
                    . " SET description = '{$localized_description}'"
                    . " WHERE lang_code = '{$lang['lang_code']}'"
                );
            }
        }

    }

    protected function pages_drop_related_ids_field()
    {

        db_query("ALTER TABLE ?:pages DROP related_ids");

    }

    protected function alter_product_weight_precision()
    {

        db_query("ALTER TABLE ?:products  CHANGE COLUMN `weight`
      `weight` DECIMAL(13,3) NOT NULL DEFAULT '0.000'");

        db_query("ALTER TABLE ?:shippings  CHANGE COLUMN `min_weight`
      `min_weight` DECIMAL(13,3) NOT NULL DEFAULT '0.000'");

        db_query("ALTER TABLE ?:shippings  CHANGE COLUMN `max_weight`
      `max_weight` DECIMAL(13,3) NOT NULL DEFAULT '0.000'");

    }

    protected function getSectionId($expected_section_id)
    {
        $expected_section = $this->sections[$expected_section_id];

        $section = db_get_row("SELECT section_id FROM ?:settings_sections WHERE name = '{$expected_section['name']}' AND type = '{$expected_section['type']}'");

        if ($section) {
            return $section['section_id'];
        }

        return false;
    }

    protected function createSection($expected_section_id)
    {
        $expected_section = $this->sections[$expected_section_id];

        db_query("
            INSERT INTO ?:settings_sections (parent_id, edition_type, name, position, type)
            VALUES (0, '{$expected_section['edition_type']}', '{$expected_section['name']}', {$expected_section['position']}, '{$expected_section['type']}');
        ");

        return $this->getSectionId($expected_section_id);
    }

    protected function createSetting(array $setting)
    {
        $setting_exists = db_get_row("SELECT object_id FROM ?:settings_objects WHERE object_id = " . $setting['object_id']);

        if ($setting_exists) {
            $setting['object_id'] = null;
        }

        $section_id = $this->getSectionId($setting['section_id']);

        if ($section_id === false) {
            $section_id = $this->createSection($setting['section_id']);
        }

        $setting['section_id'] = $section_id;

        $fields = array('object_id', 'name', 'edition_type', 'section_id', 'type', 'value', 'position', 'is_global');
        $value = array_intersect_key($setting, array_flip($fields));

        $row = db_get_row("SELECT object_id FROM ?:settings_objects WHERE name = '" . $setting['name'] . "' AND section_id = " . $section_id);

        if (empty($row)) {
            db_query("INSERT INTO ?:settings_objects (" . implode(', ', array_keys($value)) . ") VALUES ('" . implode("', '", $value) . "')");

            $row = db_get_row("SELECT object_id FROM ?:settings_objects WHERE name = '" . $setting['name'] . "' AND section_id = " . $section_id);
            $object_id = $row['object_id'];

            $langs = $this->getLangCodes();

            foreach ($langs as $lang) {
                db_query("
                    INSERT INTO ?:settings_descriptions (object_id, object_type, lang_code, value, tooltip)
                    VALUES (" . $object_id . ", 'O', '" . $lang . "', '" . $setting['description'] . "', '');
                ");
            }
        } else {
            $object_id = $row['object_id'];
        }

        return $object_id;
    }

    protected function getLangCodes()
    {
        $languages = db_get_array("SELECT lang_code FROM ?:languages");
        $codes = array();

        foreach ($languages as $lang) {
            $codes[] = $lang['lang_code'];
        }

        return $codes;
    }

    protected function add_indexes()
    {
        $table = new Table(\Tygh::$app['db'], "?:bm_grids");

        if (!$table->hasIndex('container_id')) {
            $table->addIndex(array('container_id'), array('name' => 'container_id'));
            $table->update();
        }

        $table = new Table(\Tygh::$app['db'], "?:bm_snapping");

        if (!$table->hasIndex('grid_id')) {
            $table->addIndex(array('grid_id'), array('name' => 'grid_id'));
            $table->update();
        }

        $table = new Table(\Tygh::$app['db'], "?:pages");

        if (!$table->hasIndex('id_path')) {
            $table->addIndex(array('id_path'), array('name' => 'id_path'));
            $table->update();
        }
    }

    protected function change_file_size_type()
    {
        $table = '?:product_files';
        $max_size = pow(2, 32) - 1;

        // alter tables structure
        db_query(
            "ALTER TABLE {$table}"
            . " CHANGE COLUMN `file_size`"
            . " `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0"
        );
        db_query(
            "ALTER TABLE ?:product_options"
            . " CHANGE COLUMN `max_file_size`"
            . " `max_file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0"
        );

        // update wrong sizes of EDP files
        $files_to_check = db_get_array(
            "SELECT file_id, product_id, file_path"
            . " FROM {$table}"
            . " WHERE file_size = {$max_size}"
        );
        $downloads_dir = rtrim(Registry::get('config.dir.root'), '/') . '/var/downloads/';
        foreach ($files_to_check as $file) {
            $path = $downloads_dir . $file['product_id'] . '/' . $file['file_path'];
            if (file_exists($path)) {
                $size = sprintf('%u', filesize($path));
                if ($size > $max_size) {
                    db_query(
                        "UPDATE {$table}"
                        . " SET file_size = {$size}"
                        . " WHERE file_id = {$file['file_id']}"
                    );
                }
            }
        }
    }

    protected function change_status_tables_linking()
    {

        $is_ult = Table::exists(\Tygh::$app['db'], "?:ult_status_descriptions");

        $statuses_list = db_get_array(
            "SELECT status_id, status, type"
            . " FROM ?:statuses"
        );

        // Check if table is already modified
        $status_descriptions_table = new Table(\Tygh::$app['db'], "?:status_descriptions");
        if ($status_descriptions_table->hasColumn('status_id')) {
            return;
        }
        db_query(
            "ALTER TABLE ?:status_descriptions"
            . " ADD COLUMN `status_id` mediumint(8) unsigned NOT NULL AFTER status"
        );

        if ($is_ult) {
            $ult_status_descriptions_table = new Table(\Tygh::$app['db'], "?:ult_status_descriptions");
            if ($ult_status_descriptions_table->hasColumn('status_id')) {
                return;
            }
            db_query(
                "ALTER TABLE ?:ult_status_descriptions"
                . " ADD COLUMN `status_id` mediumint(8) unsigned NOT NULL AFTER status"
            );
        }

        db_query(
            "ALTER TABLE ?:status_data"
            . " ADD COLUMN `status_id` mediumint(8) unsigned NOT NULL AFTER status"
        );

        // Update data
        foreach ($statuses_list as $status) {
            db_query(
                "UPDATE ?:status_descriptions"
                . " SET status_id = {$status['status_id']}"
                . " WHERE status = '{$status['status']}'"
                . " AND type = '{$status['type']}'"
            );
            if ($is_ult) {
                db_query(
                    "UPDATE ?:ult_status_descriptions"
                    . " SET status_id = {$status['status_id']}"
                    . " WHERE status = '{$status['status']}'"
                    . " AND type = '{$status['type']}'"
                );
            }

            db_query(
                "UPDATE ?:status_data"
                . " SET status_id = {$status['status_id']}"
                . " WHERE status = '{$status['status']}'"
                . " AND type = '{$status['type']}'"
            );
        }

        // clean up status_data table
        db_query("DELETE FROM ?:status_data WHERE status_id NOT IN (SELECT status_id FROM ?:statuses)");

        // Change primary keys
        db_query(
            "ALTER TABLE ?:status_descriptions"
            . " DROP PRIMARY KEY"
        );
        db_query(
            "ALTER TABLE ?:status_descriptions"
            . " ADD PRIMARY KEY (`status_id`,`lang_code`)"
        );
        if ($is_ult) {
            db_query(
                "ALTER TABLE ?:ult_status_descriptions"
                . " DROP PRIMARY KEY"
            );
            db_query(
                "ALTER TABLE ?:ult_status_descriptions"
                . " ADD PRIMARY KEY (`status_id`,`lang_code`,`company_id`)"
            );
        }

        db_query(
            "ALTER TABLE ?:status_data"
            . " DROP PRIMARY KEY"
        );
        db_query(
            "ALTER TABLE ?:status_data"
            . " ADD PRIMARY KEY (`status_id`,`param`)"
        );

        // Drop columns
        db_query(
            "ALTER TABLE ?:status_descriptions"
            . " DROP status, DROP type"
        );
        if ($is_ult) {
            db_query(
                "ALTER TABLE ?:ult_status_descriptions"
                . " DROP status, DROP type"
            );
        }

        db_query(
            "ALTER TABLE ?:status_data"
            . " DROP status, DROP type"
        );

    }

    protected function update_setting_timezone()
    {

        $timezone_object_id = $this->getTimezoneObjectId();

        if ($timezone_object_id) {
            $bucharest_variant_id = $this->getTimezoneVariantId('Europe/Bucharest');

            if (empty($bucharest_variant_id)) {
                db_query("INSERT INTO ?:settings_variants (`object_id`, `name`, `position`) VALUES ({$timezone_object_id}, 'Europe/Bucharest', 335)");
                db_query("UPDATE ?:settings_variants SET `position` = 415 WHERE object_id = {$timezone_object_id} AND name = 'Europe/Minsk'");

                $bucharest_variant_id = $this->getTimezoneVariantId('Europe/Bucharest');
                $istanbul_variant_id = $this->getTimezoneVariantId('Europe/Istanbul');
                $minsk_variant_id = $this->getTimezoneVariantId('Europe/Minsk');

                $this->updateVariantDescription($bucharest_variant_id, '(GMT+02:00) Bucharest');
                $this->updateVariantDescription($istanbul_variant_id, '(GMT+02:00) Athens, Beirut, Istanbul');
                $this->updateVariantDescription($minsk_variant_id, '(GMT+03:00) Minsk');
            }
        }

    }

    private function getTimezoneVariantId($name)
    {
        $object_id = $this->getTimezoneObjectId();

        if ($object_id) {
            $variant = db_get_row("SELECT variant_id FROM ?:settings_variants WHERE object_id = {$object_id} AND name = '{$name}'");

            if (!empty($variant['variant_id'])) {
                return $variant['variant_id'];
            }
        }

        return false;
    }

    private function getTimezoneObjectId()
    {
        if (static::$object_id === null) {

            $timezone_object = db_get_row("SELECT object_id, value FROM ?:settings_objects WHERE name = 'timezone'");

            if ($timezone_object) {
                static::$object_id = $timezone_object['object_id'];
            }
        }

        return static::$object_id;
    }

    private function updateVariantDescription($variant_id, $description)
    {

        $languages = db_get_array("SELECT lang_code FROM ?:languages");
        foreach ($languages as $lang) {
            db_query("
                INSERT INTO ?:settings_descriptions (object_id, object_type, lang_code, value, tooltip)
                VALUES ({$variant_id}, 'V', '{$lang['lang_code']}', '{$description}', '')
                ON DUPLICATE KEY UPDATE value = '{$description}';
            ");
        }
    }
}
