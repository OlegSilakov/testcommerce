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

$addon_ebay = db_get_row("SELECT * FROM ?:addons WHERE addon = 'ebay'");

if (!empty($addon_ebay)) {
    if (!Table::exists(\Tygh::$app['db'], "?:ebay_product_log")) {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `?:ebay_product_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) unsigned NOT NULL,
  `template_id` int(11) unsigned NOT NULL,
  `product_name` varchar(255) NOT NULL DEFAULT '',
  `template_name` varchar(255) NOT NULL DEFAULT '',
  `message` TEXT NOT NULL DEFAULT '',
  `code` varchar(32),
  `action` tinyint(2) NOT NULL DEFAULT '0',
  `type` tinyint(2) NOT NULL DEFAULT '0',
  `datetime` int(11),
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  KEY `product_id` (`product_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SQL;
        db_query($sql);
    }

    $table_products = new Table(\Tygh::$app['db'], "?:products");

    if (!$table_products->hasColumn('ebay_status')) {
        $sql = <<<SQL
UPDATE `?:products`, `?:ebay_template_products`
  SET `?:products`.`ebay_template_id` = `?:ebay_template_products`.`template_id`
  WHERE `?:products`.`product_id` = `?:ebay_template_products`.`product_id` AND `?:products`.`ebay_template_id` = 0
SQL;

        db_query($sql);

        $sql = <<<SQL
UPDATE `?:settings_objects` SET `type` = 'D'
  WHERE `name` = 'site_id' AND `section_id` = (SELECT `section_id` FROM `?:settings_sections` WHERE `name` = 'ebay' AND `type` = 'ADDON')
  LIMIT 1
SQL;
        db_query($sql);

        db_query("ALTER TABLE `?:ebay_template_products` ADD COLUMN `pictures` TEXT");
        db_query("ALTER TABLE `?:categories` ADD COLUMN `ebay_category` varchar(255)");
        db_query("ALTER TABLE `?:products` ADD COLUMN `ebay_price` decimal(12,2)");
        db_query("ALTER TABLE `?:products` ADD COLUMN `ebay_status` tinyint(2)");
        db_query("ALTER TABLE `?:products` ADD COLUMN `ebay_override_price` char(1) NOT NULL DEFAULT 'N'");
        db_query("ALTER TABLE `?:ebay_categories` ADD COLUMN `features` TEXT");
        db_query("ALTER TABLE `?:ebay_categories` ADD COLUMN `feature_update_time` int(11) unsigned NOT NULL DEFAULT '0'");
    }


    if (!Table::exists(\Tygh::$app['db'], "?:ebay_transaction_log")) {
        $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS `?:ebay_transaction_log` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `request` varchar(255) NOT NULL DEFAULT '',
      `status` tinyint(1) unsigned NOT NULL,
      `error_count` tinyint(3) unsigned NOT NULL,
      `warning_count` tinyint(3) unsigned NOT NULL,
      `success_rate` FLOAT NOT NULL DEFAULT 0,
      `start_datetime` int(11),
      `end_datetime` int(11),
      PRIMARY KEY (`id`),
      KEY `request` (`request`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SQL;
        db_query($sql);
    }

    $table = new Table(\Tygh::$app['db'], "?:ebay_template_products");

    if (!$table->hasColumn('combinations')) {
        db_query("ALTER TABLE `?:ebay_template_products` ADD COLUMN `combinations` BLOB");
    }
}
