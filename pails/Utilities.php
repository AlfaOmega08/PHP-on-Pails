<?php

function DatabaseConnect()
{
    $config = YAML::parse(file_get_contents(ROOT . '/config/database.yml'), -1);
    $config = $config[ENVIRONMENT];

    if (!isset($config['adapter']))
        throw new Exception('Adapter not specified in database.yml for environment ' . ENVIRONMENT);

    switch ($config['adapter'])
    {
        case 'mysql':
            require_once(ROOT . '/pails/database/mysql.php');
            return new MySql($config);
            break;

        default:
            throw new Exception('Invalid database adapter for environment ' . ENVIRONMENT);
    }
}

function first_valid_in_array()
{
    $args = func_get_args();
    $array = $args[0];

    for ($i = 1; $i < count($args); $i++)
        if (isset($array[$args[$i]]) && !empty($array[$args[$i]]))
            return $array[$args[$i]];

    return null;
}

function underscore($name)
{
    $name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
    $name = preg_replace('/([A-Z])([A-Z])/', '$1_$2', $name);
    return strtolower($name);
}

function dasherize($name)
{
    $name = explode('_', $name);
    $name = array_map(function($name)
    {
        return strtolower($name);
    }, $name);

    return implode('-', $name);
}

function pascalize($name)
{
    $name = explode('_', $name);
    $name = array_map(function($name)
    {
        return ucfirst($name);
    }, $name);

    return implode('', $name);
}

function pluralize($count, $singular = "", $plural = null)
{
    if ($count == 1)
        return $singular;
    else if ($plural !== null)
        return $plural;
    else
        return plural($singular);
}

function humanize($name)
{
    str_replace(array('-', '_'), ' ', $name);
    return ucfirst($name);
}

function plural($singular)
{
    global $_pluralization_table;

    if (isset($_pluralization_table[$singular]))
        return $_pluralization_table[$singular];
    else
        return $singular . 's';
}

function singular($plural)
{
    global $_pluralization_table;

    if ($key = array_search($plural, $_pluralization_table) !== false)
        return $key;
    else
    {
        if (substr($plural, -1, 1) == 's')
            return substr($plural, 0, strlen($plural) - 1);
        else
            return $plural;
    }
}

function parameterize($string, $separator = '-')
{
    $string = preg_replace('#[^\\pL\d]+#u', '-', $string);
    $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
    $string = preg_replace('/[^a-z0-9\-_]+/i', $separator, $string);

    if (!empty($separator))
    {
        $re_sep = preg_quote($separator);
        // No more than one of the separator in a row.
        $string = preg_replace("/{$re_sep}{2,}/", $separator, $string);
        $string = trim($string, $separator);
    }

    return strtolower($string);
}

$_pluralization_table = array(
    'person' => 'people',
    'gallery' => 'galleries'
);