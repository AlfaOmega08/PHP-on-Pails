<?php

class Path
{
    public static function __callStatic($name, $arguments)
    {
        $routes = Routes::get_route($name);
        if ($routes === null)
            throw new Exception("Unknown URL helper {$name}.");

        foreach ($routes as $route)
        {
            if ($route->params_count() == count($arguments))
            {
                $url = $route->url;
                $options = is_array(end($arguments)) ? array_pop($arguments) : [];

                foreach ($arguments as $arg)
                {
                    if (is_object($arg))
                        $arg = $arg->to_param();

                    $url = preg_replace('#/([:\*][A-Za-z][A-Za-z0-9_]*)#', '/' . $arg, $url, 1);
                }

                if (isset($options['format']))
                    $url .= '.' . $options['format'];

                return $url;

            }
        }

        throw new Exception("Bad number of arguments for Url::{$name}.");
    }
}