<?php

namespace ActiveRecord\ConnectionAdapters;

trait SchemaStatements
{
    function native_database_types()
    {
        return [];
    }

    function table_alias_for($table_name)
    {
        $shortened = substr($table_name, 0, $this->table_alias_length);
        return str_replace('.', '_', $shortened);
    }

    function table_exists($table_name)
    {
        return in_array($table_name, $this->tables());
    }

    function index_exists($table_name, $column_name, array $options = [])
    {
        if (!is_array($column_name))
            $column_name = [ $column_name ];

        $index_name = isset($options['name']) ? $options['name'] : $this->index_name($table_name, [ 'column' => $column_name ]);

        $unique = isset($options['unique']) && $options['unique'];
        foreach ($this->indexes($table_name) as $i)
        {
            if ($i->name == $index_name && (!$unique || $i->unique))
                return true;
        }

        return false;
    }

    function columns($table_name)
    {
        return null;
    }

    function column_exists($table_name, $column_name, $type = null, array $options = [])
    {
        foreach ($this->columns($table_name) as $c)
        {
            if ($c->name == $column_name &&
                (!$type || $c->type == $type) &&
                (!isset($options['limit']) || $c->limit == $options['limit']) &&
                (!isset($options['precision']) || $c->precision == $options['precision']) &&
                (!isset($options['scale']) || $c->scale == $options['scale']) &&
                (!isset($options['default']) || $c->default == $options['default']) &&
                (!isset($options['null']) || $c->null == $options['null'])
            )
                return true;
        }

        return false;
    }

    function create_table($table_name, array $options = [], callable $definition = null)
    {
        $td = new TableDefinition();
        if (!isset($options['id']) || $options['id'] == true)
        {
            $pk = isset($options['primary_key']) ? $options['primary_key'] : \ActiveRecord\Base::get_primary_key($table_name);
            $td->primary_key($pk);
        }

        if ($definition)
            $td = $definition($td);

        if (isset($options['force']) && $this->table_exists($table_name))
            $this->drop_table($table_name);

        $create_sql = "CREATE" . (isset($options['temporary']) ? ' TEMPORARY' : '') . " TABLE ";
        $create_sql .= "`{$table_name}` (";
        $create_sql .= $td->to_sql();
        $create_sql .= ") DEFAULT CHARSET=utf8" . (isset($options['options']) ? $options['options'] : null);

        $this->execute($create_sql);

        foreach ($td->indexes() as $c => $o)
            $this->add_index($table_name, $c, $o);
    }

    function create_join_table($table_1, $table_2, array $options = [], callable $block = null)
    {
        $join_table_name = $this->find_join_table_name($table_1, $table_2, $options);

        $column_options = [];
        if (isset($options['column_options']))
        {
            $column_options = $options['column_options'];
            unset($options['column_options']);
        }

        $column_options = array_merge([ 'null' => false ], $column_options);

        $t1_column = $table_1.singularize().foreign_key();
        $t2_column = $table_2.singularize().foreign_key();

        $this->create_table($join_table_name, $options->merge([ 'id' => false ]), function($td) use($t1_column, $t2_column, $column_options, $block)
        {
            $td->integer($t1_column, $column_options);
            $td->integer($t2_column, $column_options);
            if ($block)
                $block($td);
        });
    }

    function drop_join_table($table_1, $table_2, $options = [])
    {
        $join_table_name = $this->find_join_table_name($table_1, $table_2, $options);
        $this->drop_table($join_table_name);
    }

    function change_table($table_name, $options, callable $block)
    {
        if ($this->supports_bulk_alter() && isset($options['bulk']) && $options['bulk'])
        {
            $recorder = new \ActiveRecord\Migration\CommandRecorder($this);
            $block($this->update_table_definition($table_name, $recorder));
            $this->bulk_change_table($table_name, $recorder->commands);
        }
        else
            $block($this->update_table_definition($table_name, $this));
    }

    function rename_table($table_name, $new_name)
    {
        throw new \NotImplementedError("rename_table is not implemented");
    }

    # Although this command ignores +options+ and the block if one is given, it can be helpful
    # to provide these in a migration's +change+ method so it can be reverted.
    # In that case, +options+ and the block will be used by create_table.
    function drop_table($table_name, array $options = [])
    {
        $this->execute("DROP TABLE {$this->quote_table_name($table_name)};");
    }

    function add_column($table_name, $column_name, $type, $options = [])
    {
        $limit = isset($options['limit']) ? $options['limit'] : null;
        $precision = isset($options['precision']) ? $options['precision'] : null;
        $scale = isset($options['scale']) ? $options['scale'] : null;

        $add_column_sql = "ALTER TABLE {$this->quote_table_name($table_name)} ADD " . $this->quote_column_name($column_name) . " " . $this->type_to_sql($type, $limit, $precision, $scale);
        $this->execute($add_column_sql);
    }

    function remove_columns($table_name)
    {
        $column_names = array_splice(func_get_args(), 1);
        if (!count($column_names))
            throw new \ArgumentError("You must specify at least one column name. Example: remove_columns(:people, :first_name)");

        foreach ($column_names as $column_name)
            $this->remove_column($table_name, $column_name);
    }

    function remove_column($table_name, $column_name, $type = null, $options = [])
    {
        $this->execute("ALTER TABLE " . $this->quote_table_name($table_name) . " DROP " . $this->quote_column_name($column_name));
    }

    function change_column($table_name, $column_name, $type, $options = [])
    {
        throw new \NotImplementedError("change_column is not implemented");
    }

    function change_column_default($table_name, $column_name, $default)
    {
        throw new \NotImplementedError("change_column_default is not implemented");
    }

    function change_column_null($table_name, $column_name, $null, $default = null)
    {
        throw new \NotImplementedError("change_column_null is not implemented");
    }

    function rename_column($table_name, $column_name, $new_column_name)
    {
        throw new \NotImplementedError("rename_column is not implemented");
    }

    function add_index($table_name, $column_name, array $options = [])
    {
        list($index_name, $index_type, $index_columns, $index_options) = $this->add_index_options($table_name, $column_name, $options);
        $this->execute("CREATE {$index_type} INDEX {$this->quote_column_name($index_name)} ON {$this->quote_table_name($table_name)} ({$index_columns}){$index_options}");
    }

    function remove_index($table_name, $options = [])
    {
        $this->remove_index_($table_name, $this->index_name_for_remove($table_name, $options));
    }

    function remove_index_($table_name, $index_name)
    {
        $this->execute("DROP INDEX " . $this->quote_column_name($index_name) . " ON " . $this->quote_table_name($table_name));
    }

    function rename_index($table_name, $old_name, $new_name)
    {
        $old_index_def = null;
        foreach ($this->indexes($table_name) as $i)
        {
            if ($i->name == $old_name)
                $old_index_def = $i;
        }

        if (!$old_index_def)
            return;

        $this->remove_index($table_name, [ 'name' => $old_name ]);
        $this->add_index($table_name, $old_index_def->columns, [ 'name' => $new_name, 'unique' => $old_index_def->unique ]);
    }

    function index_name($table_name, array $options)
    {
        if ($options['column'])
        {
            if (!is_array($options['column']))
                $options['column'] = [ $options['column'] ];

            return "index_{$table_name}_on_" . implode('_and_', $options['column']);
        }
        else if ($options['name'])
            return $options['name'];
        else
            throw new \ArgumentError("You must specify the index name");
    }

    function index_name_exists($table_name, $index_name, $default)
    {
        foreach ($this->indexes($table_name) as $i)
        {
            if ($i->name == $index_name)
                return true;
        }

        return false;
    }

    function add_reference($table_name, $ref_name, $options = [])
    {
        if (isset($options['polymorphic']))
        {
            $polymorphic = $options['polymorphic'];
            unset($options['polymorphic']);
        }
        else
            $polymorphic = null;

        if (isset($options['index']))
        {
            $index_options = $options['index'];
            unset($options['index']);
        }
        else
            $index_options = null;

        $this->add_column($table_name, "{$ref_name}_id", 'integer', $options);
        if ($polymorphic)
            $this->add_column($table_name, "{$ref_name}_type", 'string', is_array($polymorphic) ? $polymorphic : $options);

        if ($index_options)
        {
            $index_names = $polymorphic ? [ "{$ref_name}_id", "{$ref_name}_type" ] : "{$ref_name}_id";
            $this->add_index($table_name, $index_names, is_array($index_options) ? $index_options : []);
        }
    }

    function add_belongs_to($table_name, $ref_name, $options = [])
    {
        $this->add_reference($table_name, $ref_name, $options);
    }

    function remove_reference($table_name, $ref_name, $options = [])
    {
        $this->remove_column($table_name, "{$ref_name}_id");
        if (isset($options['polymorphic']) && $options['polymorphic'])
            $this->remove_column($table_name, "{$ref_name}_type");
    }

    function remove_belongs_to($table_name, $ref_name, $options = [])
    {
        $this->remove_reference($table_name, $ref_name, $options);
    }

    function type_to_sql($type, $limit = null, $precision = null, $scale = null)
    {
        if ($native = $this->native_database_types()[$type])
        {
            $column_type_sql = is_array($native) ? $native['name'] : $native;

            if ($type == 'decimal') // ignore limit, use precision and scale
            {
                if ($scale == null)
                    $scale = $native['scale'];

                if ($precision == null)
                    $precision = $native['precision'];

                if ($precision)
                {
                    if ($scale)
                        $column_type_sql .= "({$precision},{$scale})";
                    else
                        $column_type_sql .= "({$precision})";
                }
                else if ($scale)
                    throw new \Exception("Error adding decimal column: precision cannot be empty if scale if specified");
            }
            else if ($type != 'primary_key')
            {
                if ($limit == null && is_array($native) && isset($native['limit']))
                    $limit = $native['limit'];

                if ($limit != null)
                    $column_type_sql .= "({$limit})";
            }

            return $column_type_sql;
        }
        else
            return $type;
    }

    function add_column_options($sql, $options)
    {
        if ($this->options_include_default($options))
            $sql .= " DEFAULT " . $this->quote($options['default'], $options['column']);
        if (isset($options['null']) && $options['null'] == false)
            $sql .= " NOT NULL";

        return $sql;
    }

    function distinct($columns, $order_by)
    {
        return "DISTINCT {$columns}";
    }

    function add_timestamps($table_name)
    {
        $this->add_column($table_name, 'created_at', 'datetime');
        $this->add_column($table_name, 'updated_at', 'datetime');
    }

    function remove_timestamps($table_name)
    {
        $this->remove_column($table_name, 'updated_at');
        $this->remove_column($table_name, 'created_at');
    }

    protected function add_index_sort_order($option_strings, $column_names, $options = [])
    {
        if (is_array($options) && (isset($options['order'])))
        {
            $order = $options['order'];
            if (is_array($order))
            {
                foreach ($column_names as $name)
                {
                    if (isset($order[$name]))
                        $option_strings[$name] .= " " . strtoupper($order[$name]);
                }
            }
            else if (is_string($order))
            {
                foreach ($column_names as $name)
                    $option_strings[$name] .= " " . strtoupper($order);
            }
        }

        return $option_strings;
    }

    protected function quoted_columns_for_index($column_names, $options = [])
    {
        $option_strings = [];
        foreach ($column_names as $name)
            $option_strings[$name] = '';

        if ($this->supports_index_sort_order())
            $option_strings = $this->add_index_sort_order($option_strings, $column_names, $options);

        $ret = [];
        foreach ($column_names as $name)
            $ret[] = $this->quote_column_name($name) . $option_strings[$name];

        return $ret;
    }

    protected function options_include_default($options)
    {
        return isset($options['default']) && !((!isset($options['null']) || $options['null'] == false) && is_null($options['default']));
    }

    protected function add_index_options($table_name, $column_name, array $options = [])
    {
        if (!is_array($column_name))
            $column_names = [ $column_name ];
        else
            $column_names = $column_name;

        $index_name = $this->index_name($table_name, [ 'column' => $column_names ]);

        $index_type = isset($options['unique']) && $options['unique'] ? "UNIQUE" : "";
        if (isset($options['name']))
            $index_name = $options['name'];

        $max_index_length = isset($options['internal']) && $options['internal'] ? $this->index_name_length() : $this->allowed_index_name_length();

        if ($this->supports_partial_index())
            $index_options = isset($options['where']) ? " WHERE {$options['where']}" : "";
        else
            $index_options = "";

        if (strlen($index_name) > $this->max_index_length())
            throw new \Exception("Index name '{$index_name}' on table '{$table_name}' is too long; the limit is {$this->max_index_length()} characters");

        if ($this->index_name_exists($table_name, $index_name, false))
            throw new \Eception("Index name '{$index_name}' on table '{$table_name}' already exists");

        $index_columns = implode(', ', $this->quoted_columns_for_index($column_names, $options));

        return [ $index_name, $index_type, $index_columns, $index_options ];
    }

    protected function index_name_for_remove($table_name, $options = [])
    {
        $index_name = $this->index_name($table_name, $options);

        if (!$this->index_name_exists($table_name, $index_name, true))
        {
            if (is_array($options) && isset($options['name']))
            {
                $options_without_column = $options;
                unset($options_without_column['column']);
                $index_name_without_column = $this->index_name($table_name, $options_without_column);

                if ($this->index_name_exists($table_name, $index_name_without_column, false))
                    return $index_name_without_column;
            }

            throw new \Exception("Index name '{$index_name}' on table '{$table_name}' does not exist");
        }

        return $index_name;
    }

    protected function rename_table_indexes($table_name, $new_name)
    {
        foreach ($this->indexes($new_name) as $index)
        {
            $generated_index_name = $this->index_name($table_name, [ 'column' => $index->columns ]);
            if ($generated_index_name == $index->name)
                $this->rename_index($new_name, $generated_index_name, $this->index_name($new_name, [ 'column' => $index->columns ]));
        }
    }

    protected function rename_column_indexes($table_name, $column_name, $new_column_name)
    {
        foreach ($this->indexes($table_name) as $index)
        {
            if (!in_array($new_column_name, $index->columns))
                continue;
            $old_columns = $index->columns;
            $old_columns[array_search($new_column_name, $old_columns)] = $column_name;
            $generated_index_name = $this->index_name($table_name, [ 'column' => $old_columns ]);
            if ($generated_index_name == $index->name)
                $this->rename_index($table_name, $generated_index_name, $this->index_name($table_name, [ 'column' => $index->columns ]));
        }
    }
}
