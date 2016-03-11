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

class F434T435
{
    protected $store_data = array();
    protected $main_sql_filename = 'ult_F434T435.sql';

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

        $this->last_view_obsolete();

        $this->decimal_filter_values();

        $this->add_rocketgate();

        $this->remove_disable_shipping_setting();

        $this->add_free_shipping_option();

        $this->rename_and_remove_payment_processors();

        $this->add_trash_category();

        $this->remove_payment_dependencies_for_free_mode();

        $this->update_available_addons_for_free_mode();

        $this->exclude_store_import_for_trial_mode();

        $this->change_installed_upgrades_table_field_types();

        $this->rename_deprecated_eway_payments();

        $this->dhl_services();

        General::updateAltLanguages('language_values', 'name');
        General::updateAltLanguages('ult_language_values', array('name', 'company_id'));
        General::updateAltLanguages('settings_descriptions', array('object_id', 'object_type'));

        General::setEmptyProgressBar();
        General::setEmptyProgressBar();
        General::setEmptyProgressBar();
        General::setEmptyProgressBar();

        return true;
    }

    protected function last_view_obsolete()
    {

        db_query("DELETE FROM ?:views WHERE object = ''");

    }

    protected function decimal_filter_values()
    {

        db_query(
            "ALTER TABLE ?:product_filters CHANGE `round_to` `round_to` VARCHAR(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '1';"
        );

    }

    protected function add_rocketgate()
    {

        $processor_id = db_get_row("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'rocketgate.php'");

        // update payments processor params, if exist
        if (!empty($processor_id)) {
            $processor_id = $processor_id['processor_id'];

            $params = db_get_row("SELECT * FROM ?:payments WHERE processor_id = {$processor_id}");
            if (!empty($params['processor_params'])) {
                $params = unserialize($params['processor_params']);
                unset($params['md5_hash_value']);
                $params['avsmode'] = 'true';
                $params['scrubmode'] = 'true';
                $params = addslashes(serialize($params));
                db_query("UPDATE ?:payments SET processor_params = '{$params}' WHERE processor_id = {$processor_id}");
            }
        }

        db_query("DELETE FROM ?:payment_processors WHERE processor_script = 'rocketgate.php'");
        db_query("INSERT INTO ?:payment_processors (processor, processor_script, processor_template, admin_template, callback, type) VALUES ('Rocketgate-Native', 'rocketgate-native.php', 'views/orders/components/payments/cc.tpl', 'rocketgate-native.tpl', 'N', 'P')");

        // update payments, if exist
        if (!empty($processor_id)) {
            $new_processor_id = db_get_row("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'rocketgate-native.php'");

            if (!empty($new_processor_id)) {
                $new_processor_id = $new_processor_id['processor_id'];
                db_query("UPDATE ?:payments SET processor_id = {$new_processor_id} WHERE processor_id = {$processor_id}");
            }
        }

    }

    protected function remove_disable_shipping_setting()
    {

        $option = db_get_row("SELECT object_id FROM ?:settings_objects WHERE name = 'disable_shipping'");
        if (!empty($option['object_id'])) {
            $object_id = $option['object_id'];
            db_query("DELETE FROM ?:settings_objects      WHERE object_id = {$object_id}");
            db_query("DELETE FROM ?:settings_descriptions WHERE object_id = {$object_id}");
        }

    }

    protected function add_free_shipping_option()
    {

        $table = new Table(\Tygh::$app['db'], "?:shippings");
        if (!$table->hasColumn('free_shipping')) {
            db_query("ALTER TABLE ?:shippings ADD COLUMN free_shipping CHAR(1) NOT NULL DEFAULT 'N'");
        }

    }

    protected function rename_and_remove_payment_processors()
    {

        $processors_table = "?:payment_processors";

        $replacements = array(
            'pay_read.php' => 'Payer',
            'p21_simple_api.php' => 'Payment 21 (Simple API)',
            'paypoint.php' => 'PayPoint (Gateway Hosted)',
            'paypoint_xml.php' => 'PayPoint (Gateway Freedom)',
            'proxypay3.php' => 'Eurobank ProxPay3',
            'deltapay.php' => 'Alpha e-Pay',
            'directebanking.php' => 'Sofort banking',
            'echo.php' => 'ECHO (Intuit Payment Solutions)',
            'ogone_direct.php' => 'Ingenico Payment Services [ex Ogone Direct]',
            'ogone_web.php' => 'Ingenico Payment Services [ex Ogone Web]'
        );

        foreach ($replacements as $processor_script => $processor) {
            db_query(
                "UPDATE {$processors_table}"
                . " SET processor = '{$processor}'"
                . " WHERE processor_script = '{$processor_script}'"
            );
        }

        $removals = array('epassporte.php', 'mcpe.php', 'pri_background.php', 'pri_form.php');

        $removed_processors_ids = db_get_array(
            "SELECT processor_id FROM {$processors_table}"
            . " WHERE processor_script IN ('" . implode("','", $removals) . "')"
        );
        foreach ($removed_processors_ids as $i => $processor) {
            $removed_processors_ids[$i] = $processor['processor_id'];
        }

        if ($removed_processors_ids) {
            db_query(
                "DELETE FROM {$processors_table}"
                . " WHERE processor_id IN (" . implode(',', $removed_processors_ids) . ')'
            );

            db_query(
                "UPDATE ?:payments SET processor_id = 0, processor_params = ''"
                . " WHERE processor_id IN (" . implode(',', $removed_processors_ids) . ')'
            );
        }

    }

    protected function add_trash_category()
    {

        $table = new Table(\Tygh::$app['db'], "?:categories");

        if (!$table->hasColumn('is_trash')) {
            db_query(
                "ALTER TABLE ?:categories"
                . " ADD COLUMN is_trash CHAR(1) NOT NULL DEFAULT 'N'"
            );
        }

    }

    protected function remove_payment_dependencies_for_free_mode()
    {

        $data = db_get_row("SELECT data FROM ?:storage_data WHERE data_key = 'addons_snapshots'");
        $addons_snapshots = $data['data'];

        $addon_hash = md5('payment_dependencies');

        if (strpos($addons_snapshots, $addon_hash) === false) {
            $addons_snapshots .= ",{$addon_hash}";
            db_query("UPDATE ?:storage_data SET data = '{$addons_snapshots}' WHERE data_key = 'addons_snapshots'");
        }

    }

    protected function update_available_addons_for_free_mode()
    {
        $this->updateHash(
            array(
                'image_zoom', 'social_buttons'
            ),
            array(
                'bestsellers', 'blog', 'call_requests', 'email_marketing', 'newsletters', 'store_import', 'tags'
            )
        );

    }

    protected function exclude_store_import_for_trial_mode()
    {
        $this->updateHash(array(), array('store_import:trial'));

    }

    protected function change_installed_upgrades_table_field_types()
    {
        if (Table::exists(\Tygh::$app['db'], '?:installed_upgrades')) {
            db_query("ALTER TABLE ?:installed_upgrades CHANGE `conflicts` `conflicts` LONGTEXT  CHARACTER SET utf8 COLLATE utf8_general_ci NULL;");
        }
    }

    protected function rename_deprecated_eway_payments()
    {

        $processor_scripts = array(
            'eway_form.php',
            'eway_direct.php',
            'eway_shared.php'
        );
        // rename processor scripts
        db_query(
            "UPDATE ?:payment_processors"
            . " SET processor = CONCAT('Deprecated ', processor)"
            . " WHERE processor_script IN ('" . implode("','", $processor_scripts) . "')"
        );

    }

    protected function dhl_services()
    {

        // check if upgrade required by searching for deprecated service code
        $upgrade_required = db_get_row(
            "SELECT service_id"
            . " FROM ?:shipping_services"
            . " WHERE module = 'dhl'"
            . " AND code = 'IE:SAT'"
            . " LIMIT 1"
        );

        if ($upgrade_required) {
            // get old services
            $old_services = db_get_array(
                "SELECT service_id"
                . " FROM ?:shipping_services"
                . " WHERE module = 'dhl'"
            );
            foreach ($old_services as $i => $service) {
                $old_services[$i] = $service['service_id'];
            }

            $languages = db_get_array(
                "SELECT lang_code"
                . " FROM ?:languages"
            );

            // delete old services
            db_query(
                "DELETE"
                . " FROM ?:shipping_services"
                . " WHERE module = 'dhl'"
            );

            // add new services
            $services = array(
                'X' => array(
                    'desc' => 'DHL Express Envelope'
                ),
                'D' => array(
                    'desc' => 'DHL Express Worldwide'
                ),
                'U' => array(
                    'desc' => 'DHL Express Worldwide EU'
                ),
                'K' => array(
                    'desc' => 'DHL Express 9:00'
                ),
                'L' => array(
                    'desc' => 'DHL Express 10:30'
                ),
                'T' => array(
                    'desc' => 'DHL Express 12:00'
                ),
                'N' => array(
                    'desc' => 'DHL Domestic Express'
                ),
                '7' => array(
                    'desc' => 'DHL Express Easy'
                ),
            );

            // add descriptions
            $service_descriptions = array();
            foreach ($services as $code => $description) {
                $services[$code]['id'] = db_query(
                    "INSERT"
                    . " INTO ?:shipping_services (status, module, code)"
                    . " VALUES ('A', 'dhl', '{$code}')"
                );
                foreach ($languages as $lang) {
                    $service_descriptions[] = "({$services[$code]['id']}, '{$description['desc']}', '{$lang['lang_code']}')";
                }
            }
            db_query(
                "INSERT"
                . " INTO ?:shipping_service_descriptions (service_id, description, lang_code)"
                . " VALUES " . implode(',', $service_descriptions)
            );

            // set defaults
            $default_service = reset($services);
            $default_service = $default_service['id'];
            db_query(
                "UPDATE ?:shippings"
                . " SET service_id = " . $default_service
                . " WHERE service_id IN (" . implode(',', $old_services) . ')'
            );
        }

    }

    protected function updateHash(array $include_addons, array $exclude_addons)
    {
        $data = db_get_row("SELECT data FROM ?:storage_data WHERE data_key = 'addons_snapshots'");
        $addons_snapshots = explode(',', $data['data']);

        foreach ($exclude_addons as $addon) {
            $addon = md5($addon);

            if (!in_array($addon, $addons_snapshots)) {
                $addons_snapshots[] = $addon;
            }
        }

        foreach ($include_addons as $addon) {
            $index = array_search(md5($addon), $addons_snapshots);

            if ($index !== false) {
                unset($addons_snapshots[$index]);
            }
        }

        $addons_snapshots = implode(',', $addons_snapshots);
        db_query("UPDATE ?:storage_data SET data = '{$addons_snapshots}' WHERE data_key = 'addons_snapshots'");
    }

}
