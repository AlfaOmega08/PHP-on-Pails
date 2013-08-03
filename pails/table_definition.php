<?php

class TableDefinition
{
    private $columns = [];

    public function column($name, $type, $options = [])
    {
        if (isset($this->columns[$name]))
            $column = &$this->columns[$name];
        else
            $column = $this->new_column_definition($name, $type);

        if (isset($options['limit']))
            $limit = $options['limit'];
        else if (isset($this->types[$type]['limit']))
            $limit = $this->types[$type]['limit'];
        else
            $limit = null;

        $column->limit = $limit;
        $column->precision = isset($options['precision']) ? $options['precision'] : null;
        $column->scale = isset($options['scale']) ? $options['scale'] : null;
        $column->default = isset($options['default']) ? $options['default'] : null;
        $column->null = isset($options['null']) ? $options['null'] : null;

        return $this;
    }

    public function string($name)
    {
        return $this->column($name, 'string');
    }

    public function integer($name)
    {
        return $this->column($name, 'integer');
    }

    public function datetime($name)
    {
        return $this->column($name, 'datetime');
    }

    public function text($name)
    {
        return $this->column($name, 'text');
    }

    public function timestamps()
    {
        $this->column('created_at', 'datetime', [ 'null' => false ]);
        $this->column('updated_at', 'datetime', [ 'null' => false ]);

        return $this;
    }

    public function to_sql()
    {
        $cols = [];

        foreach ($this->columns as $col)
            $cols[] = $col->to_sql();

        return implode(', ', $cols);
    }

    public function primary_key($name)
    {
        $this->column($name, 'primary_key');
    }

    private function &new_column_definition($name, $type)
    {
        $this->columns[$name] = new ColumnDefinition($name, $type);
        return $this->columns[$name];
    }
}