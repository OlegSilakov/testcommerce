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

// 1. Product /product-name/ URL
$variant = db_get_row("SELECT v.variant_id, s.object_id FROM ?:settings_objects s INNER JOIN ?:settings_variants v ON s.object_id=v.object_id WHERE s.name = 'seo_product_type' and v.name='product_file_nohtml'");
if (!empty($variant['variant_id'])) {
    db_query("UPDATE ?:settings_descriptions SET value='/product-name/' WHERE object_id=$variant[variant_id] and value='/product-name'");
}
// 2. Category /parent-category/category-[page2].html URL
$variant = db_get_row("SELECT v.variant_id, s.object_id FROM ?:settings_objects s INNER JOIN ?:settings_variants v ON s.object_id=v.object_id WHERE s.name = 'seo_product_type' and v.name='product_category_nohtml'");
if (!empty($variant['variant_id'])) {
    db_query("UPDATE ?:settings_descriptions SET value='/category-name/[subcategory-name/]product-name/' WHERE object_id=$variant[variant_id] and value='/category-name/[subcategory-name/]product-name'");
}
// 3. Category /category-name/[subcategory-name/]product-name/ URL
$variant = db_get_row("SELECT v.variant_id, s.object_id FROM ?:settings_objects s INNER JOIN ?:settings_variants v ON s.object_id=v.object_id WHERE s.name = 'seo_category_type' and v.name='category'");
if (!empty($variant['variant_id'])) {
    db_query("UPDATE ?:settings_descriptions SET value='/parent-category/category-[page-2].html' WHERE object_id=$variant[variant_id] and value='/parent-category/category/[page2/].html'");
}
// 4. Other type /item/ URL
$variant = db_get_row("SELECT v.variant_id, s.object_id FROM ?:settings_objects s INNER JOIN ?:settings_variants v ON s.object_id=v.object_id WHERE s.name = 'seo_other_type' and v.name='directory'");
if (!empty($variant['variant_id'])) {
    db_query("UPDATE ?:settings_descriptions SET value='/item/' WHERE object_id=$variant[variant_id] and value='/item'");
}
// 5. Category /parent-category/category/[page2/] URL
$variant = db_get_row("SELECT v.variant_id, s.object_id FROM ?:settings_objects s INNER JOIN ?:settings_variants v ON s.object_id=v.object_id WHERE s.name = 'seo_category_type' and v.name='category_nohtml'");
if (!empty($variant['variant_id'])) {
    db_query("UPDATE ?:settings_descriptions SET value='/parent-category/category/[page-2/]' WHERE object_id=$variant[variant_id] and value='/parent-category/category/[page2/]'");
}
// 6. Category /category[-page2].html URL
$variant = db_get_row("SELECT v.variant_id, s.object_id FROM ?:settings_objects s INNER JOIN ?:settings_variants v ON s.object_id=v.object_id WHERE s.name = 'seo_category_type' and v.name='file'");
if (!empty($variant['variant_id'])) {
    db_query("UPDATE ?:settings_descriptions SET value='/category[-page-2].html' WHERE object_id=$variant[variant_id] and value='/category[-page2].html'");
}
// 7. Category /category/[page2/] URL
$variant = db_get_row("SELECT v.variant_id, s.object_id FROM ?:settings_objects s INNER JOIN ?:settings_variants v ON s.object_id=v.object_id WHERE s.name = 'seo_category_type' and v.name='root_category'");
if (!empty($variant['variant_id'])) {
    db_query("UPDATE ?:settings_descriptions SET value='/category/[page-2/]' WHERE object_id=$variant[variant_id] and value='/category/[page2/]'");
}
