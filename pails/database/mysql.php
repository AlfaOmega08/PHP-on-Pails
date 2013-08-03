<?php

class Mysql
{
    private $link;

    public function __construct($args)
    {
        if (!isset($args['database']))
            throw new Exception('Database name not specified for MySql adapter');

        $host = isset($args['server']) ? $args['server'] : '127.0.0.1';
        $user = isset($args['username']) ? $args['username'] : 'root';
        $pass = isset($args['password']) ? $args['password'] : '';
        $port = isset($args['port']) ? $args['port'] : 3306;

        $this->link = new mysqli($host, $user, $pass, $args['database'], $port);
        if ($this->link->connect_error)
            throw new Exception('MySql Connection Error: ' . $this->link->connect_error);

        if (isset($args['encoding']))
            $this->link->set_charset($args['encoding']);
    }

    public function query($sql)
    {
        $result = $this->link->query($sql);
        if ($result === false)
            throw new Exception("Query '{$sql}' failed: {$this->link->error}.");

        if ($result === true)
            return true;

        if (function_exists('mysqli_fetch_all'))
            return $result->fetch_all(MYSQL_ASSOC);

        $data = [];
        while ($row = $result->fetch_assoc())
            $data[] = $row;
        return $data;
    }

    public function table_exists($table_name)
    {
        return count($this->query("SHOW TABLES LIKE '{$table_name}';")) == 1;
    }

    public function columns($table_name)
    {
        $rows = $this->query("SHOW COLUMNS FROM `$table_name`;");
        $res = [];

        foreach ($rows as $r)
        {
            $res[$r['Field']] = $r;
            unset($res[$r['Field']]['Field']);
        }

        return $res;
    }

    function escape_string($str)
    {
        return $this->link->real_escape_string($str);
    }

    public function database_type($type, $limit = null)
    {
        if ($type == 'integer')
        {
            switch ($limit)
            {
                case '1': return [ 'name' => 'tinyint' ];
                case '2': return [ 'name' => 'smallint' ];
                case '3': return [ 'name' => 'mediumint' ];
                case null:
                case 4:
                case 11:
                    return [ 'name' => 'int(11)' ];
                case 5:
                case 6:
                case 7:
                case 8:
                    return [ 'name' => 'bigint' ];
            }
        }

        $types = [
            'primary_key' => [
                'name' => 'bigint AUTO_INCREMENT PRIMARY KEY'
            ],
            'string' => [
                'name' => 'varchar',
                'limit' => 255
            ],
            'datetime' => [
                'name' => 'datetime'
            ],
            'text' => [
                'name' => 'text'
            ],
            'boolean' => [
                'name' => 'boolean'
            ],
        ];

        return $types[$type];
    }

    public function last_id()
    {
        return $this->link->insert_id;
    }
}
