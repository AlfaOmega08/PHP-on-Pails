<?php

namespace ActiveRecord\ConnectionAdapters;

trait Quoting
{
    function quote($value, $column = null)
    {
        if (is_string($value))
        {
            if (is_null($column))
                return "'" . $this->quote_string($value) . "'";

            switch ($column->type)
            {
                case 'binary':
                    return "'" . $this->quote_string($column->string_to_binary($value)) . "'";
                case 'integer':
                    return intval($value);
                case 'float':
                    return floatval($value);
                default:
                    return "'" . $this->quote_string($value) . "'";
            }
        }
        else if (is_bool($value))
        {
            if ($column && $column->type == 'integer')
                return $value ? '1' : '0';
            else
                return $value ? $this->quoted_true() : $this->quoted_false();
        }
        else if (is_null($value))
            return 'NULL';
        else if (is_int($value) || is_float($value))
            return $value;
        else if (is_array($value))
            return "'" . $this->quote_string(Yaml::dump($value)) . "'";
        else if (is_a($value, 'Date') || is_a($value, 'Time'))
            return "'" . $this->quoted_date($value) . "'";
        else
        {
            $obj_as_array = json_decode(json_encode($value), true);
            return "'" . $this->quote_string(Yaml::dump($obj_as_array)) . "'";
        }
    }

    function quote_column_name($column_name)
    {
        return $column_name;
    }

    function quote_string($value)
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('\'', '\\\'', $value);
        return $value;
    }

    function quote_table_name($table_name)
    {
        return $this->quote_column_name($table_name);
    }

    function quote_table_name_for_assignment($table, $attr)
    {
        return $this->quote_table_name("{$table}.{$attr}");
    }

    function quoted_date($value)
    {
        global $config;
        $zone_conversion_method = $config->active_record->default_timezone == 'utc' ? 'getutc' : 'getlocal';

        if (method_exists($value, $zone_conversion_method))
            $value = $value->$zone_conversion_method();

        return $value->to_s('db');
    }

    function quoted_true()
    {
        return 't';
    }

    function quoted_false()
    {
        return 'f';
    }
}