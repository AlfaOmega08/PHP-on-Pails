<?php

namespace ActiveModel;

trait Base
{
    private static $read_attributes = [];
    private static $write_attributes = [];
    private $attributes;

    protected static function attr_reader()
    {
        $args = func_get_args();
        foreach ($args as $arg)
            self::$read_attributes[] = $arg;
    }

    protected static function attr_writer()
    {
        $args = func_get_args();
        foreach ($args as $arg)
            self::$write_attributes[] = $arg;
    }

    protected static function attr_accessor()
    {
        $args = func_get_args();
        foreach ($args as $arg)
        {
            self::$read_attributes[] = $arg;
            self::$write_attributes[] = $arg;
        }
    }

    function __trait_constructors()
    {
        $methods = new Ay(get_class_methods($this));
        $methods.keep_if(function($method)
        {
            return preg_match('/^__trait_init_(.*)$/', $method);
        })->each(function($m)
        {
            $this->$m();
        });
    }

    function __get($var)
    {
        if (in_array($var, self::$read_attributes))
        {
            if (!isset(self::$attributes[$var]))
                self::$attributes[$var] = null;

            $get_accessor = 'get_' . $var;
            if (is_callable($this->$get_accessor))
                return $this->$get_accessor();

            return self::$attributes[$var];
        }

        return NULL;
    }

    function __set($var, $value)
    {
        if (in_array($var, self::$write_attributes))
        {
            $set_accessor = 'set_' . $var;
            if (is_callable($this->$set_accessor))
                return $this->$set_accessor($value);
            else
                return $this->attributes[$var] = $value;
        }

        return $value;
    }

    function __call($method, $arguments)
    {
        if (is_callable($this->$method))
            return call_user_func_array([ $this, $method ], $arguments);

        throw new \Exception("NoMethodError: $method");
    }
}
