<?php

abstract class Model
{
    use ActiveModel\Callbacks;
    use ActiveRecord\Pagination;
    use ActiveModel\SecurePassword;
    use ActiveModel\Validations;

    private static $link = null;

    private $table_name;

    private $select = '*';
    private $where;
    private $group;
    private $order;
    private $limit;
    private $offset;

    private $columns;
    private $row;
    private $is_new;

    private $has_many_relations = [];
    static private $accessors = [];
    private $mass_assignable = [];

    public function __construct($row = null, $new = null)
    {
        if (self::$link == null)
        {
            self::define_callback('save');
            self::define_callback('destroy');
            self::connect();
        }

        $this->table_name = plural(strtolower(get_class($this)));
        if (!self::$link->table_exists($this->table_name))
            throw new Exception("Table '" . $this->table_name . "' does not exist.");

        $this->columns = self::$link->columns($this->table_name);

        $this->init();

        if ($row !== null)
        {
            foreach ($row as $col => $val)
                $this->$col = $val;

            $this->is_new = false;
        }
        else
            $this->is_new = true;

        if ($new !== null)
            $this->is_new = $new;
    }

    public static function model()
    {
        return new static;
    }

    public function select($args)
    {
        if (is_string($args))
            $this->select = $args;
        else if (is_array($args))
            $this->select = implode(', ', $args);

        return $this;
    }

    public function where()
    {
        $args = func_get_args();

        if (is_string($args[0]))
        {
            $pattern = array_shift($args);
            if (($count = substr_count($pattern, '?')) != 0)
            {
                if ($count != count($args))
                    throw new Exception("Bad count of arguments for query building");

                foreach ($args as $arg)
                {
                    // WHERE `col` IN (1, 2, 3)
                    if (is_array($arg))
                    {
                        $arg = implode(', ', array_map(function($el)
                        {
                            return "'" . self::$link->escape_string($el) . "'";
                        }, $arg));

                        $pattern = preg_replace('/\?/', "($arg)", $pattern, 1);
                    }
                    else
                    {
                        $arg = self::$link->escape_string($arg);
                        $pattern = preg_replace('/\?/', "'$arg'", $pattern, 1);
                    }
                }
            }
            else if (($count = substr_count($pattern, ':')) != 0)
            {
                $args = $args[1];
                if ($count != count($args))
                    throw new Exception("Bad count of arguments for query building");

                foreach ($args as $key => $arg)
                {
                    // WHERE `col` IN (1, 2, 3)
                    if (is_array($arg))
                    {
                        $arg = implode(', ', array_map(function($el)
                        {
                            return "'" . self::$link->escape_string($el) . "'";
                        }, $arg));

                        $pattern = preg_replace("/:$key/", "($arg)", $pattern, 1);
                    }
                    else
                    {
                        $arg = self::$link->escape_string($arg);
                        $pattern = preg_replace("/:$key/", "'$arg'", $pattern, 1);
                    }
                }
            }

            $this->where = '(' . $pattern . ')';
        }
        else if (is_array($args[0]))
        {
            $args = $args[0];

            $query = [];
            foreach ($args as $key => $value)
            {
                $elem = "`$key`";

                if (is_null($value))
                    $elem .= ' IS NULL';
                else if (is_bool($value))
                    $elem .= ' = ' . ($value ? 'TRUE' : 'FALSE');
                else if (is_array($value))
                {
                    $value = implode(', ', array_map(function($el)
                    {
                        return "'" . self::$link->escape_string($el) . "'";
                    }, $value));

                    $elem .= " IN ($value)";
                }
                else
                    $elem .= " = '" . self::$link->escape_string($value) . "'";

                $query[] = $elem;
            }

            $this->where = '(' . implode(' AND ', $query) . ')';
        }
        else
            throw new Exception("What the hell where you trying to do?");

        return $this;
    }

    public function limit($l)
    {
        $this->limit = $l;
        return $this;
    }

    public function offset($l)
    {
        $this->offset = $l;
        return $this;
    }

    public function group($args)
    {
        $this->group = $args;
        return $this;
    }

    public function all()
    {
        $query = "SELECT {$this->select} FROM `{$this->table_name}`";
        if ($this->where)
            $query .= " WHERE " . $this->where;
        if ($this->group)
            $query .= " GROUP BY " . $this->group;
        if ($this->order)
            $query .= " ORDER BY " . $this->order;
        if ($this->offset || $this->limit)
        {
            $query .= " LIMIT ";
            if ($this->offset !== null)
                $query .= intval($this->offset);
            else
                $query .= '0';

            if ($this->limit)
                $query .= ", " . intval($this->limit);
        }

        $query .= ';';
        return $this->build_models(self::$link->query($query));
    }

    public function count($col = '*')
    {
        $query = "SELECT COUNT({$col}) AS count FROM `{$this->table_name}`";
        if ($this->where)
            $query .= " WHERE " . $this->where;
        if ($this->group)
            $query .= " GROUP BY " . $this->group;
        if ($this->offset || $this->limit)
        {
            $query .= " LIMIT ";
            if ($this->offset !== null)
                $query .= $this->offset;
            else
                $query .= '0';

            if ($this->limit)
                $query .= ", " . $this->limit;
        }

        $query .= ';';
        return self::$link->query($query)[0]['count'];
    }

    public static function find($id)
    {
        $result = self::model()->where([ 'id' => $id ])->all();
        if (empty($result))
            throw new Exception("Object with id = $id not found");

        return $result[0];
    }

    private static function connect()
    {
        $config = Yaml::parse(file_get_contents(ROOT . '/config/database.yml'), -1);
        $config = $config[ENVIRONMENT];

        if (!isset($config['adapter']))
            throw new Exception('Adapter not specified in database.yml for environment ' . ENVIRONMENT);

        switch ($config['adapter'])
        {
            case 'mysql':
                require_once(ROOT . '/pails/database/mysql.php');
                self::$link = new Mysql($config);
                break;

            default:
                throw new Exception('Invalid database adapter for environment ' . ENVIRONMENT);
        }
    }

    private function build_models($rows)
    {
        $result = [];
        $class = get_class($this);

        foreach ($rows as $row)
            $result[] = new $class($row, false);

        return $result;
    }

    public function is_new_record()
    {
        return $this->is_new;
    }

    public function to_param()
    {
        if (!isset($this->row['id']))
            $this->row['id'] = '';
        return $this->row['id'];
    }

    public function save()
    {
        $this->fire_callbacks('before_save');

        if ($this->is_invalid())
            return false;

        if ($this->is_new_record())
        {
            $to_save = [];
            foreach ($this->row as $col => $val)
            {
                if (isset($this->columns[$col]))
                    $to_save[$col] = $val;
            }

            $columns = implode(', ', array_map(function($col)
            {
                return "`" . $col . "`";
            }, array_keys($to_save)));

            $values = implode(', ', array_map(function($val)
            {
                return '"' . self::$link->escape_string($val) . '"';
            }, array_values($to_save)));

            $sql = "INSERT INTO `{$this->table_name}` ";
            $sql .= "($columns) VALUES($values);";

            self::$link->query($sql);

            $this->id = self::$link->last_id();
        }
        else
        {
            $to_save = new Ay();
            foreach ($this->row as $col => $val)
            {
                if (isset($this->columns[$col]) && $col != 'id')
                    $to_save[$col] = $val;
            }

            $columns = $to_save->map(function($col, $val) { return "`{$col}`"; });
            $values = $to_save->map(function($col, $val) { return '"' . self::$link->escape_string($val) . '"'; });

            $sets = new Ay();
            for ($i = 0; $i < count($columns); $i++)
                $sets[] = $columns[$i] . ' = ' . $values[$i];

            $sql = "UPDATE `{$this->table_name}` SET " . $sets->join(', ');

            echo $sql;
            self::$link->query($sql);
        }

        $this->fire_callback('after_save');
        return true;
    }

    public function update_attributes($attributes)
    {
        foreach ($attributes as $key => $val)
            $this->$key = $val;

        return $this->save();
    }

    public function has_many($childs, array $options = [])
    {
        $default_options = [
            'class_name' => $childs,
        ];

        $options = array_merge($default_options, $options);

        if ($options['dependent'])
            $this->register_callback('before_destroy', 'dependent_destroy');

        $this->has_many_relations[$childs] = $options;
    }

    public function __get($var_name)
    {
        if (method_exists($this, $var_name))
            return $this->$var_name();

        if (in_array($var_name, self::$accessors))
            return $this->$var_name;

        if (isset($this->row[$var_name]))
            return $this->row[$var_name];

        if (isset($this->has_many_relations[$var_name]))
        {
            $relation = $this->has_many_relations[$var_name];
            $model = call_user_func([ pascalize(singular($relation['class_name'])), 'model' ]);

            if (isset($relation['foreign_key']))
                $col = $relation['foreign_key'] . '_id';
            else
                $col = underscore(get_class($this)) . '_id';

            return $model->where("`{$col}` = '{$this->id}'")->all();
        }

        return NULL;
    }

    public function __set($var_name, $value)
    {
        if (method_exists(get_class($this), $var_name))
            return self::$var_name($value);
        else if (method_exists($this, 'set_' . $var_name))
        {
            $func = 'set_' . $var_name;
            return $this->$func($value);
        }

        if (in_array($var_name, self::$accessors))
            $this->$var_name = $value;

        $this->row[$var_name] = $value;

        return $value;
    }

    public static function __callStatic($method, $arguments)
    {
        if (preg_match('/find_by_(.*)/', $method, $matches))
        {
            $result = self::model()->where("`{$matches[1]}` = \"{$arguments[0]}\"")->all();
            if (empty($result))
                return null;

            return $result[0];
        }

        return false;
    }

    public function init()
    {
        // Placeholder function
    }

    static function attr_accessor($var_or_array)
    {
        if (is_array($var_or_array))
        {
            foreach ($var_or_array as $var)
                self::attr_accessor($var);
            return;
        }

        $var = $var_or_array;

        self::$accessors[] = $var;
    }

    public function attr_accessible($var_or_array)
    {
        if (is_array($var_or_array))
        {
            foreach ($var_or_array as $var)
                $this->attr_accessible($var);
            return;
        }

        $this->mass_assignable[] = $var_or_array;
    }

    public function delete()
    {
        if (func_num_args() == 0 && $this->where === null)
        {
            if ($this->id === null)
                $this->where([ 'id' => $this->id ]);
            throw new Exception("Delete must be called with a WHERE clause defined. If you want to delete all records use delete_all");
        }

        if (func_num_args())
            call_user_func_array([ $this, 'where' ], func_get_args());

        $sql = "DELETE FROM `{$this->table_name}` WHERE {$this->where};";
        self::$link->query($sql);
    }

    public function delete_all()
    {
        if (func_num_args())
            call_user_func_array([ $this, 'where' ], func_get_args());

        $sql = "DELETE FROM `{$this->table_name}`";
        if ($this->where)
            $sql .= " WHERE {$this->where};";
        self::$link->query($sql);
    }

    public function destroy()
    {
        if (func_num_args() == 0 && $this->where === null)
        {
            if ($this->id === null)
                throw new Exception("Destroy must be called with a WHERE clause defined. If you want to delete all records use destroy_all");
            $this->where([ 'id' => $this->id ]);
        }

        if (func_num_args())
            call_user_func_array([ $this, 'where' ], func_get_args());

        $models = $this->all();
        foreach ($models as $model)
        {
            $model->fire_callbacks('before_destroy');
            $sql = "DELETE FROM `{$this->table_name}` WHERE `id` = '{$model->id}';";
            self::$link->query($sql);
            $model->destroyed = true;
            $model->fire_callbacks('after_destroy');
        }
    }

    public function destroy_all()
    {
        if (func_num_args())
            call_user_func_array([ $this, 'where' ], func_get_args());

        $sql = "DELETE FROM `{$this->table_name}`";
        if ($this->where)
            $sql .= " WHERE {$this->where};";
        self::$link->query($sql);
    }

    public function dependent_destroy()
    {
        foreach ($this->has_many_relations as $relation => $options)
        {
            $model = call_user_func([ pascalize(singular($options['class_name'])), 'model' ]);

            if (!isset($options['dependent']))
                continue;

            switch ($options['dependent'])
            {
                case 'destroy':
                    $f_key = isset($options['foreign_key']) ? $options['foreign_key'] . "_id" : $col = underscore(get_class($this)) . '_id';
                    $model->destroy([ $f_key => $this->id ]);
                    break;
                case 'delete_all':
                    $f_key = isset($options['foreign_key']) ? $options['foreign_key'] . "_id" : $col = underscore(get_class($this)) . '_id';
                    $model->delete_all([ $f_key => $this->id ]);
                    break;
                case 'nullify':
                    $f_key = isset($options['foreign_key']) ? $options['foreign_key'] . "_id" : $col = underscore(get_class($this)) . '_id';
                    $model->update_all("`{$f_key} = NULL`", "`{$f_key} = '{$this->id}'`");
                    break;
            }
        }
    }
}
