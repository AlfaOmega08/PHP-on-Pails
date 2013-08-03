<?php

include_once("ENVIRONMENT.php");
define('ROOT', dirname(__FILE__));

require_once(ROOT . '/pails/Utilities.php');

spl_autoload_register(function($class_name)
{
    $paths = array(
        ROOT . '/pails/',
        ROOT . '/pails/lib/'
    );

    $class_name = underscore(str_replace('\\', '/', $class_name));

    foreach ($paths as $path)
    {
        if (file_exists($path . $class_name . ".php"))
        {
            require_once($path . $class_name . '.php');
            return;
        }
    }
});

function get_link()
{
    $config = Yaml::parse(file_get_contents(ROOT . '/config/database.yml'), -1);
    $config = $config[ENVIRONMENT];

	var_dump($config);

    if (!isset($config['adapter']))
        throw new Exception('Adapter not specified in database.yml for environment ' . ENVIRONMENT);

    switch ($config['adapter'])
    {
        case 'mysql':
            require_once(ROOT . '/pails/database/mysql.php');
            return  new Mysql($config);
            break;

        default:
            throw new Exception('Invalid database adapter for environment ' . ENVIRONMENT);
    }
}

$link = get_link();
if (!$link->table_exists('schema_migrations'))
    $link->query("CREATE TABLE `schema_migrations` (version VARCHAR(20) PRIMARY KEY);");

$migrations = scandir(ROOT . '/db/migrations', SCANDIR_SORT_ASCENDING);

if ($argc > 1 && $argv[1] == 'rollback')
{
    rollback();
    die();
}


foreach ($migrations as $migration)
{
    if ($migration == '.' || $migration == '..')
        continue;

    $ts = explode('_', $migration)[0];
    $res = $link->query("SELECT * FROM `schema_migrations` WHERE version = '$ts'");

    if (count($res) != 0)
        continue;

    $class = explode('.', $migration)[0];
    $class = array_splice(explode('_', $class), 1);
    $class = implode('_', $class);
    $class = pascalize($class);

    include(ROOT . '/db/migrations/' . $migration);
    $class = new $class;
    $class->up();

    $link->query("INSERT INTO `schema_migrations` VALUES('{$ts}');");
}


function rollback()
{
    global $link, $migrations;

    $last = $link->query("SELECT * FROM `schema_migrations` ORDER BY version DESC LIMIT 0,1;")[0]['version'];

    foreach ($migrations as $migration)
    {
        if ($migration == '.' || $migration == '..')
            continue;

        $ts = explode('_', $migration)[0];
        if ($ts != $last)
            continue;

        $class = explode('.', $migration)[0];
        $class = array_splice(explode('_', $class), 1);
        $class = implode('_', $class);
        $class = pascalize($class);

        include(ROOT . '/db/migrations/' . $migration);
        $class = new $class;
        $class->down();

        $link->query("DELETE FROM `schema_migrations` WHERE version = '{$ts}';");

        break;
    }
}
