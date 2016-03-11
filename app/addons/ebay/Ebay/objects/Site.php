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


namespace Ebay\objects;
use Ebay\Client;

/**
 * Class Site
 * @package Ebay\objects
 */
class Site
{
    /** Synchronization period for site (7 days) */
    const SYNCHRONIZATION_PERIOD = 604800;

    /**
     * Check needle synchronization sites from ebay
     * @return bool
     */
    public static function isNeedSynchronization()
    {
        $time = fn_get_storage_data('ebay_site_synchronization_time');

        return empty($time) || $time + self::SYNCHRONIZATION_PERIOD < time();
    }

    /**
     * Update sites from ebay
     * @throws \Exception
     */
    public static function synchronization()
    {
        $client = Client::instance();
        $result = $client->getEbayDetails('SiteDetails');

        if ($result) {
            if (!$result->isSuccess()) {
                throw new \Exception(implode("\n", $result->getErrorMessages()));
            }

            $sites = $result->getSiteDetails();

            if (!empty($sites)) {
                $data = array();

                foreach ($sites as $item) {
                    $data[] = array(
                        'site_id' => $item->site_id,
                        'site' => $item->site,
                    );
                }

                db_query('DELETE FROM ?:ebay_sites WHERE 1');
                db_query('INSERT INTO ?:ebay_sites ?m', $data);

                static::setLastSynchronizationTime(time());
            }
        } else {
            throw new \Exception(implode("\n", $client->getErrors()));
        }
    }

    /**
     * set last synchronization time
     * @param int $time
     */
    public static function setLastSynchronizationTime($time)
    {
        fn_set_storage_data('ebay_site_synchronization_time', $time);
    }

    /**
     * Remove all synchronization times
     */
    public static function clearLastSynchronizationTime()
    {
        fn_set_storage_data('ebay_site_synchronization_time', null);
    }
}