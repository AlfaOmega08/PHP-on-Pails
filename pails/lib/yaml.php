<?php

if (!function_exists('yaml_parse'))
    require_once(ROOT . '/pails/3rd-party/Spyc.php');

class Yaml
{
    public static function parse($input)
    {
        if (function_exists('yaml_parse'))
            return yaml_parse($input, -1);
        else
            return Spyc::YAMLLoadString($input);
    }

    public static function dump($array)
    {
        if (function_exists('yaml_emit'))
            return yaml_emit($array, YAML_UTF8_ENCODING, YAML_CRLN_BREAK);
        else
            return Spyc::YAMLDump($array);
    }
}
