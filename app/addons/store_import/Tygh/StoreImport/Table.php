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

class Table
{
    /**
     * @var string Table name
     */
    private $table;

    /**
     * @var \Tygh\Database\Connection Database connection
     */
    private $db;

    /**
     * Table constructor
     *
     * @param \Tygh\Database\Connection $db    Database connection
     * @param string                    $table Table name
     */
    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * Checks if table has column
     *
     * @param string $column Column name
     *
     * @return bool True if column exists
     */
    public function hasColumn($column)
    {
        $fields = fn_get_table_fields(str_replace('?:', '', $this->table));

        return in_array($column, $fields);
    }

    /**
     * Adds column to table
     *
     * @param string $name       Column name
     * @param string $type       Column type
     * @param array  $properties Additional properties for column (default value, null values, etc.)
     *
     * @return Table $this
     */
    public function addColumn($name, $type, $properties = array())
    {
        if ($type == 'string') {
            $type = 'varchar';
        }
        if (isset($properties['limit'])) {
            $type .= "({$properties['limit']})";
        }
        $not_null = '';
        $default = '';
        if (isset($properties['null']) && $properties['null'] === false) {
            $not_null = 'NOT NULL';
        }
        if (isset($properties['default'])) {
            $default = "DEFAULT {$properties['default']}";
        }
        $this->db->query("ALTER TABLE ?p ADD COLUMN ?f ?p {$not_null} {$default}", $this->table, $name, $type);

        return $this;
    }

    /**
     * phinx-compatibility mock function
     *
     * @return bool Always true
     */
    public function update()
    {
        return true;
    }

    /**
     * Checks if table exists
     *
     * @param \Tygh\Database\Connection $db    Database connection
     * @param string                    $table Table name
     *
     * @return bool True if table exists in database
     */
    public static function exists($db, $table)
    {
        $table = db_process($table);

        return !!$db->getField("SHOW TABLES LIKE ?s", $table);
    }

    /**
     * Checks if table has index on specified column
     *
     * @param string $column_name Column name
     *
     * @return bool True if index on column exists
     */
    public function hasIndex($column_name)
    {
        return !!$this->db->getColumn("SHOW INDEX FROM ?p WHERE column_name = ?s", $this->table, $column_name);
    }

    /**
     * Adds index
     *
     * @param array $column_names Columns to create index for
     * @param array $properties   Index properties
     *
     * @return Table $this
     */
    public function addIndex($column_names, $properties = array())
    {
        $default_properties = array(
            'name' => implode('_', $column_names),
            'unique' => false,
        );
        $properties = array_merge($default_properties, $properties);

        $this->db->query(
            "CREATE INDEX ?p ?p"
             . " ON ?p (?p)",
            $properties['name'],
            $properties['unique'] ? 'UNIQUE' : '',
            $this->table,
            implode(',', $column_names)
        );

        return $this;
    }

    /**
     * Removes index
     *
     * @param string $index_name Columns to remove index for
     *
     * @return Table $this
     */
    public function removeIndex($index_name)
    {
        $this->db->query(
            "DROP INDEX ?p"
            . " ON ?p",
            implode('_', $index_name),
            $this->table
        );

        return $this;
    }

}
