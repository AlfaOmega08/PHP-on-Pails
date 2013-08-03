<?php

define('ROOT', dirname(__FILE__));

include_once("ENVIRONMENT.php");

require_once(ROOT . '/config/environments/' . ENVIRONMENT . '.php');
require_once(ROOT . '/pails/Utilities.php');

spl_autoload_register(function($class_name)
{
    $paths = array(
        ROOT . '/pails/',
        ROOT . '/pails/lib/'
    );

    $user_paths = array(
        ROOT . '/app/controllers/',
        ROOT . '/app/models/',
        ROOT . '/app/helpers/'
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

    foreach ($user_paths as $path)
    {
        if (file_exists($path . $class_name . ".php"))
        {
            require_once($path . $class_name . '.php');
            return;
        }
    }
});

require(ROOT . '/config/routes.php');

//setlocale(LC_CTYPE, 'it_IT.UTF-8');

$route = Routes::resolve(Request::fullpath());
if (extension_loaded('newrelic'))
{
    newrelic_disable_autorum();
    newrelic_name_transaction($route['controller'] . '/' . $route['action']);
}

if ($route['controller'] === null || $route['action'] === null)
    throw new Exception("Bad route for that URL (controller or action missing).");

$controller = pascalize($route['controller'] . "_controller");
$controller = new $controller;
$controller->action($route['action']);
