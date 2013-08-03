<?php

namespace ActionDispatch\Http;

trait Parameters
{
    static $path_parameters;

    public static function &parameters()
    {
        static $result = null;
        if ($result !== null)
            return $result;

        $files = self::normalize_files();

        return $result = array_merge_recursive(self::request_parameters(), self::query_parameters(), $files, self::path_parameters());
    }

    public static function path_parameters()
    {
        return self::$path_parameters;
    }

    public static function set_path_parameters($params)
    {
        self::$path_parameters = $params;
    }

    public static function normalize_files()
    {
        if (empty($_FILES))
            return [];

        $first = reset($_FILES)['tmp_name'];

        if (!is_array($first))
            return $_FILES;

        $result = [];
        foreach ($_FILES as $key1 => $value1)
        {
            foreach ($value1 as $key2 => $value2)
            {
                foreach ($value2 as $key3 => $value3)
                    $result[$key1][$key3][$key2] = $value3;
            }
        }

        return $result;
    }
}