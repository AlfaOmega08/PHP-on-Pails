<?php

namespace ActiveModel;

class Errors implements \ArrayAccess
{
    use \ActiveModel\Base;

    private $messages = [];

    function __construct($base)
    {
        $this->base = $base;
    }

    function clear()
    {
        $this->messages = [];
    }

    function includes($error)
    {
        return array_key_exists($error, $this->messages);
    }

    function offsetGet($error)
    {
        return $this->messages[$error];
    }

    function offsetSet($error, $value)
    {
        $this->messages[$error] = $value;
    }

    function delete($error)
    {
        unset($this->messages[$error]);
    }

    function offsetUnset($error)
    {
        $this->delete($error);
    }

    function offsetExists($error)
    {
        return $this->includes($error);
    }

    function add($field, $message)
    {
        if (isset($this->messages[$field]))
            $this->messages[$field][] = $message;
        else
            $this->messages[$field] = [ $message ];
    }

    function is_empty()
    {
        return count($this->messages) == 0;
    }
}
