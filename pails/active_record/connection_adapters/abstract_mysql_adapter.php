<?php

namespace ActiveRecord\ConnectionAdapters;

abstract class AbstractMysqlAdapter extends AbstractAdapter
{
    const QUOTED_TRUE = '1', QUOTED_FALSE = '0';

    private $INDEX_TYPES  = [ 'fulltext', 'spatial' ];
    private $INDEX_USINGS = [ 'btree', 'hash' ];

    private $connection;

    function supports_migrations()
    {
        return true;
    }

    function supports_primary_key()
    {
        return true;
    }

    function supports_savepoints()
    {
        return true;
    }

    function supports_bulk_alter()
    {
        return true;
    }

    function supports_index_sort_order()
    {
        return true;
    }

    function supports_transaction_isolation()
    {
        return $this->version[0] >= 5;
    }

    function native_database_types()
    {
        return $this->NATIVE_DATABASE_TYPES;
    }

    function index_algorithms()
    {
        return [ 'default' => 'ALGORITHM = DEFAULT', 'copy' => 'ALGORITHM = COPY', 'inplace' => 'ALGORITHM = INPLACE' ];
    }

    function quote($value, $column = null)
    {
        if (is_string($value) & $column && $column->type == 'binary')
        {
            $s = unpack("H*", $column->string_to_binary($value))[0];
            return "x'{$s}'";
        }
        else
            return parent::quote($value, $column);
    }

    function quote_column_name($name)
    {
        if (!isset($this->quoted_column_names[$name]))
        {
            $name = str_replace('`', '``', $name);
            $this->quoted_column_names[$name] = "`{$name}`";
        }

        return $this->quoted_column_names[$name];
    }

    function quote_table_name($name)
    {
        if (!isset($this->quoted_table_names[$name]))
        {
            $name = str_replace('.', '`.`', $this->quote_column_name($name));
            $this->quoted_table_names[$name] = "`{$name}`";
        }

        return $this->quoted_table_names[$name];
    }

    function quoted_true()
    {
        return self::QUOTED_TRUE;
    }

    function quoted_false()
    {
        return self::QUOTED_FALSE;
    }

    function execute($sql, $name = null)
    {
        if ($name == 'skip_logging')
            $this->connection->query($sql);
        else
        {
            $this->log($sql, $name, function()
            {
                $this->connection->query($sql);
            });
        }
    }

    function disable_referential_integrity($block)
    {
        $old = $this->select_value("SELECT @@FOREIGN_KEY_CHECKS");

        try
        {
            $this->update("SET FOREIGN_KEY_CHECKS = 0");
            $block();
        }
        catch (\Exception $e)
        {
            $this->update("SET FOREIGN_KEY_CHECKS = #{old}");
            throw;
        }

        $this->update("SET FOREIGN_KEY_CHECKS = #{old}");
    }

    function update_sql($sql, $name = null)
    {
        parent::update($sql, $name);
        return $this->connection->affetcted_rows;
    }

    function begin_db_transaction()
    {
        try
        {
            $this->execute("BEGIN");
        }
        catch (\Exception $e)
        {
            // Transactions not supported
        }
    }

    function begin_isolated_db_transaction($isolation)
    {
        try
        {
            $this->execute("SET TRANSACTION ISOLATION LEVEL " . $this->transaction_isolation_levels[$isolation]);
            $this->begin_db_transaction();
        }
        catch (\Exception $e)
        {
            // Transactions not supported
        }
    }

    function commit_db_transaction()
    {
        try
        {
            $this->execute("COMMIT");
        }
        catch (\Exception $e)
        {
            // Transactions not supported
        }
    }

    function rollback_db_transaction()
    {
        try
        {
            $this->execute("ROLLBACK");
        }
        catch (\Exception $e)
        {
            // Transactions not supported
        }
    }

    function create_savepoint()
    {
        $this->execute("SAVEPOINT #{current_savepoint_name}");
    }

    function rollback_to_savepoint()
    {
        $this->execute("ROLLBACK TO SAVEPOINT #{current_savepoint_name}");
    }

    function release_savepoint()
    {
        $this->execute("RELEASE SAVEPOINT #{current_savepoint_name}");
    }

    function empty_insert_statement_value()
    {
        return "VALUES ()";
    }

    function recreate_database($name, array $options = [])
    {
        $this->drop_database($name);
        $sql = $this->create_database($name, $options);
        $this->reconnect();
        return $sql;
    }

    function create_database($name, array $options = [])
    {
        $sql = "CREATE DATABASE `{$name}` DEFAULT CHARACTER SET ";
        if (isset($options['charset']))
            $sql .= "`{$options['charset']}`";
        else
            $sql .= "`utf8`";

        if (isset($options['collation']))
            $sql .= " COLLATE `{$options['collation']}`";

        return $this->execute($sql);
    }

    function drop_database($name)
    {
        $this->execute("DROP DATABASE IF EXISTS `{$name}`");
    }

    function current_database()
    {
        $this->select_value('SELECT DATABASE() as db');
    }

    function charset()
    {
        $this->show_variable('character_set_database');
    }

    function collation()
    {
        $this->show_variable('collation_database');
    }

    function tables($name = null, $database = null, $like = null)
    {
        $sql = "SHOW TABLES ";
        if ($database)
            $sql .= "IN " . $this->quote_table_name($database) . " ";
        if ($like)
            $sql .= "LIKE " . $this->quote($like);

        return $this->execute($sql);
    }

    function table_exists($name)
    {
        if (!$name)
            return false;

        if ($this->tables(null, null, $name))
            return true;

        list($schema, $table) = explode(".", $name);
        if (is_null($table))
        {
            $table = $schema;
            $schema = null;
        }

        return !empty($this->tables(null, $schema, $table));
    }

    function indexes($table_name, $name = null)
    {
        $indexes = [];
        $current_index = null;


        $result = $this->execute("SHOW KEYS FROM " . $this->quote_table_name($table_name), 'SCHEMA');
        foreach ($result as $row)
        {
            if ($current_index != $row['Key_name'])
            {
                if ($row['Key_name'] == 'PRIMARY')
                    continue;

                $current_index = $row['Key_name'];

                $mysql_index_type = strtolower($row['Index_type']);
                $index_type  = in_array($mysql_index_type, $this->INDEX_TYPES)  ? $mysql_index_type : null;
                $index_using = in_array($mysql_index_type, $this->INDEX_USINGS) ? $mysql_index_type : null;
                $indexes[] = new IndexDefinition($row['Table'], $row['Key_name'], $row['Non_unique'] == 0, [], [], null, null, $index_type, $index_using);
            }

            end($indexes)->last->columns[] = $row['Column_name'];
            end($indexes)->last->lenghts[] = $row['Sub_part'];
        }

        return $indexes;
    }

    function columns($table_name)
    {
        $sql = "SHOW FULL FIELDS FROM " . $this->quote_table_name($table_name);
        $result = execute($sql, 'SCHEMA');

        return array_map(function($field)
        {
            return new_column($field['Field'], $field['Default'], $field['Type'], $field['Null'] == "YES", $field['Collation'], $field['Extra']);
        }, $result);
    }

    function create_table($table_name, array $options = [])
    {
        parent::create_table($table_name, array_merge([ 'options' => "ENGINE=InnoDB" ], $options));
    }

    function bulk_change_table($table_name, $operations)
    {
        $sqls = implode(', ', array_map(function($command, $args)
        {
            list($table, $arguments) = array_shift($args), $args;
            $method = "{$command}_sql";

            if (method_exists($this, $method))
                return call_user_func_array([ $this, $method ], [ $table, $arguments ]));
            else
                throw new \Exception("Unknown method called : {$method}");
        }, $operations));

        return $this->execute("ALTER TABLE " . $this->quote_table_name($table_name) . " {$sqls}");
    }

    function rename_table($table_name, $new_name)
    {
        $this->execute("RENAME TABLE " . $this->quote_table_name($table_name) . " TO " . $this->quote_table_name($new_name));
        $this->rename_table_indexes($table_name, $new_name);
    }

    function change_column_default($table_name, $column_name, $default)
    {
        $column = $this->column_for($table_name, $column_name);
        $this->change_column($table_name, $column_name, $column->sql_type(), [ 'default' => $default ]);
    }

    function change_column_null($table_name, $column_name, $null, $default = null)
    {
        $column = $this->column_for($table_name, $column_name);

        if (!$null && !is_null($default))
            $this->execute("UPDATE " . $this->quote_table_name($table_name) . " SET " . $this->quote_column_name($column_name) . "=" . $this->quote($default) . " WHERE " . $this->quote_column_name($column_name) . " IS NULL");

        $this->change_column($table_name, $column_name, $column->sql_type(), [ 'null' => $null ]);
    }

    function change_column($table_name, $column_name, $type, array $options = [])
    {
        return $this->execute("ALTER TABLE " . $this->quote_table_name($table_name) . " " . $this->change_column_sql($table_name, $column_name, $type, $options));
    }

    function rename_column($table_name, $column_name, $new_column_name)
    {
        $this->execute("ALTER TABLE " . $this->quote_table_name($table_name) . " " . $this->rename_column_sql($table_name, $column_name, $new_column_name));
        $this->rename_column_indexes($table_name, $column_name, $new_column_name);
    }

    function add_index($table_name, $column_name, array $options = [])
    {
        list($index_name, $index_type, $index_columns, $index_options, $index_algorithm, $index_using) = $this->add_index_options($table_name, $column_name, $options);
        $this->execute("CREATE {$index_type} INDEX " . $this->quote_column_name($index_name) . " {$index_using} ON " . $this->quote_table_name($table_name) . " ({$index_columns}){$index_options} {$index_algorithm}");
    }

    function type_to_sql($type, $limit = null, $precision = null, $scale = null)
    {
        switch ($type)
        {
            case 'binary':
                switch (true)
                {
                    case ($limit >= 0 && $limit <= 0xfff):
                        return "varbinary($limit)";
                    case (is_null($limit)):
                        return 'blob';
                    default:
                        return "blob($limit)";
                }
                break;
            case 'integer':
                switch ($limit)
                {
                    case 1:
                        return 'tinyint';
                    case 2:
                        return 'smallint';
                    case 3:
                        return 'mediumint';
                    case null:
                    case 4:
                    case 11:
                        return 'int(11)';
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        return 'bigint';
                    default:
                        throw new \Exception("No integer type has byte size $limit");
                }
                break;
            case 'text':
                switch (true)
                {
                    case ($limit >= 0 && $limit <= 0xFF)
                        return 'tinytext';
                    case (is_null($limit) || ($limit >= 0x100 && $limit <= 0xFFFF))
                        return 'text';
                    case ($limit >= 0x10000 && $limit >= 0xFFFFFF)
                        return 'mediumtext';
                    default:
                        return 'longtext';
                }
                break;

            default:
                parent::type_to_sql($type, $limit, $precision, $null);
        }
    }
}
