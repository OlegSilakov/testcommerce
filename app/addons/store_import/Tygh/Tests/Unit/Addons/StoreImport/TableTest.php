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

namespace Tygh\Tests\Unit\Addons\StoreImport;

use Tygh\StoreImport\Table;
use Tygh\Tests\Unit\ATestCase;

class TableTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    protected $app;

    protected function setUp()
    {
        define('BOOTSTRAP', true);
        define('AREA', 'A');
        define('CART_LANGUAGE', 'en');

        $this->requireCore('functions/fn.database.php');
        $this->requireMockFunction('fn_set_hook');

        $this->app = \Tygh\Tygh::createApplication();

        // Driver
        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->setMethods(array('escape', 'query', 'insertId'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));
        $this->app['db.driver'] = $driver;

        // Connection
        $this->app['db'] = $this->getMockBuilder('\Tygh\Database\Connection')
            ->setMethods(array('error', 'getColumn'))
            ->setConstructorArgs(array($driver))
            ->getMock();
    }

    public function hasIndexProvider()
    {
        return array(
            array('field1', array('table'), true),
            array('field2', array(),        false)
        );
    }

    /**
     * @dataProvider hasIndexProvider
     */
    public function testHasIndex($column_name, $index_data, $expected)
    {
        $this->app['db']
            ->expects($this->once())
            ->method('getColumn')
            ->with('SHOW INDEX FROM ?p WHERE column_name = ?s', '?:table', $column_name)
            ->will($this->returnValue($index_data));

        $t = new Table($this->app['db'], "?:table");
        $this->assertEquals($expected, $t->hasIndex($column_name));
    }

    public function addIndexProvider()
    {
        return array(
            array(array('field1'), array('name' => 'field1', 'unique' => false)),
            array(array('field2'), array('name' => 'field2', 'unique' => true))
        );
    }

    /**
     * @dataProvider addIndexProvider
     */
    public function testAddIndex($column_names, $properties)
    {
        $columns = implode(',', $column_names);
        $unique = !empty($properties['unique']) ? 'UNIQUE' : '';

        $this->app['db.driver']
            ->expects($this->once())
            ->method('query')
            ->with("CREATE INDEX {$properties['name']} {$unique} ON table ({$columns})");

        $t = new Table($this->app['db'], '?:table');
        $t->addIndex($column_names, $properties);
    }
}
