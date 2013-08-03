<?php

class ColumnDefinition
{
    public $name, $type;
    public $limit, $precision, $scale, $default, $null;

    public function __construct($name, $type, $options = [])
    {
        $this->name = $name;
        $this->type = $type;

        if (isset($options['limit']))
            $this->limit = $options['limit'];
        if (isset($options['precision']))
            $this->precision = $options['precision'];
        if (isset($options['scale']))
            $this->scale = $options['scale'];
        if (isset($options['default']))
            $this->default = $options['default'];
        if (isset($options['null']))
            $this->null = $options['null'];
    }

    public function to_sql()
    {
        $sql = "`{$this->name}` " . $this->type_to_sql();

        if ($this->type != 'primary_key')
        {
            if ($this->default !== null)
            {
                $sql .= " DEFAULT ";

                if ($this->type == 'boolean')
                    $sql .= $this->default ? 'TRUE' : 'FALSE';
                else
                    $sql .= "'{$this->default}'";
            }

            if ($this->null === false)
                $sql .= " NOT NULL";
        }

        return $sql;
    }

    public function type_to_sql()
    {
        if ($native = $this->native_database_types($this->type))
        {
            $column_type_sql = $native['name'];

            if ($this->type == 'decimal') // ignore limit, use precision and scale
            {
                if ($this->scale == null)
                    $this->scale = $native['scale'];

                if ($this->precision == null)
                    $this->precision = $native['precision'];

                if ($this->precision)
                {
                    if ($this->scale)
                        $column_type_sql .= "({$this->precision},{$this->scale})";
                    else
                        $column_type_sql .= "({$this->precision})";
                }
                else if ($this->scale)
                    throw new Exception("Error adding decimal column: precision cannot be empty if scale if specified");
            }
            else if ($this->type != 'primary_key')
            {
                if ($this->limit == null && isset($native['limit']))
                    $this->limit = $native['limit'];

                if ($this->limit != null && $this->type != 'integer')
                    $column_type_sql .= "({$this->limit})";
            }

            return $column_type_sql;
        }
        else
            return $this->type;
    }

    private function native_database_types($type)
    {
        $link = DatabaseConnect();
        return $link->database_type($type, $this->limit);
    }
}
