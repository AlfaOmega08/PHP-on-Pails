<?php

namespace ActiveModel;

trait Callbacks
{
    private static $accepted_callbacks = [];

    static function define_callback($name)
    {
        self::$accepted_callbacks['before_' . $name] = [];
        self::$accepted_callbacks['after_' . $name] = [];
    }

    static function register_callback($name, $method)
    {
        if (isset(self::$accepted_callbacks[$name]))
            self::$accepted_callbacks[$name][] = $method;
    }

    function fire_callbacks($name)
    {
        if (isset(self::$accepted_callbacks[$name]))
        {
            foreach (self::$accepted_callbacks[$name] as $callback)
            {
                call_user_func([$this, $callback]);
            }
        }
    }
}