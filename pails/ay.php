<?php

class KeyError extends Exception {}

class Ay implements ArrayAccess, Iterator
{
    private $data;

    function __construct(array $data = [])
    {
        $this->data = $data;
    }

    function clear()
    {
        $this->data = [];
        return $this;
    }

    function delete($key, callable $function = null)
    {
        if (isset($this->data[$key]))
        {
            $data = $this->data[$key];
            unset($this->data[$key]);
            return $data;
        }

        if (!is_null($function))
            return $function($key);

        return null;
    }

    function delete_if(callable $function)
    {
        foreach ($this->data as $key => $value)
        {
            if ($function($key, $value))
                delete($key);
        }

        return $this;
    }

    function each(callable $function)
    {
        foreach ($this->data as $key => $val)
            $function($key, $val);
    }

    function each_pair(callable $function)
    {
        foreach ($this->data as $key => $val)
            $function([ $key, $val ]);
    }

    function each_key(callable $function)
    {
        foreach ($this->data as $key => $val)
            $function($key);
    }

    function each_value(callable $function)
    {
        foreach ($this->data as $val)
            $function($val);
    }

    function fetch($key, $default = null)
    {
        if (isset($this->data[$key]))
            return $this->data[$key];

        if (func_num_args() == 1)
            throw new KeyError();
        else
        {
            if (is_callable($default))
                return $default($key);
            else
                return $default;
        }
    }

    function has_key($key)
    {
        return array_key_exists($key, $this->data);
    }

    function has_value($value)
    {
        return in_array($value, $this->data);
    }

    function is_empty()
    {
        return count($this->data) == 0;
    }

    function keep_if(callable $function)
    {
        foreach ($this->data as $key => $value)
        {
            if (!$function($key, $value))
                delete($key);
        }

        return $this;
    }

    function keys()
    {
        return array_keys($this->data);
    }

    function last()
    {
        $copy = $this->data;
        return end($copy);
    }

    function length()
    {
        return count($this->data);
    }

    function join($separator = '')
    {
        return implode($separator, $this->data);
    }

    function map($callback)
    {
        $ret = [];

        foreach ($this->data as $key => $val)
            $ret[] = $callback($key, $val);

        return $ret;
    }

    function merge($other)
    {
        $first = $this->data;

        if (is_a($other, 'Ay'))
            return new Ay(array_merge($first, $other->data));
        else if (is_array($other))
            return new Ay(array_merge($first, $other));

        throw new Exception("Argument to merge must be an array or an instance of Ay");
    }

    function merge_($other)
    {
        if (is_a($other, 'Ay'))
        {
            $this->data = array_merge($this->data, $other->data);
            return $this;
        }
        else if (is_array($other))
        {
            $this->data = array_merge($this->data, $other);
            return $this;
        }

        throw new Exception("Argument to merge must be an array or an instance of Ay");
    }

    function shift()
    {
        return array_shift($this->data);
    }

    static function wrap($data)
    {
        if (is_a($data, 'Ay'))
            return $data;

        if (is_array($data))
            return new Ay($data);

        return new Ay([ $data ]);
    }

    function values()
    {
        return array_values($this->data);
    }

    function values_at()
    {
        $ret = [];
        foreach (func_get_args() as $key)
            $ret[] = $this[$key];

        return $ret;
    }

    function offsetSet($offset, $value)
    {
        if (is_null($offset))
            return $this->data[] = $value;
        else
            return $this->data[$offset] = $value;
    }

    function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    function rewind()
    {
        reset($this->data);
    }

    function current()
    {
        return current($this->data);
    }

    function key()
    {
        return key($this->data);
    }

    function next()
    {
        return next($this->data);
    }

    function valid()
    {
        $key = key($this->data);
        return $key !== null && $key !== false;
    }
}