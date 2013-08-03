<?php

class Migration
{
    private static $link = null;

    public function __construct()
    {
        if (self::$link == null)
            self::connect();
    }

    protected function drop_table($name)
    {
        self::$link->query("DROP TABLE `{$name}`;");
    }

    protected function create_table($name, array $options = [], callable $definition = null)
    {
        $td = new TableDefinition();
        if (!isset($options['id']) || $options['id'] == true)
            $td->primary_key(isset($options['primary_key']) ? $options['primary_key'] : 'id');

        if ($definition)
            $td = $definition($td);

        if (isset($options['force']) && self::$link->table_exists($name))
            $this->drop_table($name);

        $sql = "CREATE" . (isset($options['temporary']) ? ' TEMPORARY' : '') . " TABLE ";
        $sql .= "`{$name}` (";
        $sql .= $td->to_sql();
        $sql .= ") DEFAULT CHARSET=utf8" . (isset($options['options']) ? $options['options'] : null);

        self::$link->query($sql);
    }

    protected function add_column($table_name, $column_name, $type, $options = [])
    {
        $col = new ColumnDefinition($column_name, $type, $options);
        $add_column_sql = "ALTER TABLE `{$table_name}` ADD " . $col->to_sql();
        self::$link->query($add_column_sql);
    }

    protected function remove_column($table_name, $column_name)
    {
        $sql = "ALTER TABLE `{$table_name}` DROP {$column_name}";
        self::$link->query($sql);
    }

    protected function change_column($table_name, $column_name, $type, array $options = [])
    {
        $col = new ColumnDefinition($column_name, $type, $options);
        $sql = "ALTER TABLE `{$table_name}` CHANGE `{$column_name}` ";
        $sql .= $col->to_sql();
        self::$link->query($sql);
    }

    private static function connect()
    {
        self::$link = DatabaseConnect();
    }
}