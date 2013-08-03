<?php

define('ROOT', dirname(__FILE__));

if ($argc < 2)
    die("Usage: php generate.php [generator] [arguments]\n");

if (file_exists("pails/lib/generators/{$argv[1]}"))
    $path = "pails/lib/generators/{$argv[1]}";
else if (file_exists("lib/generators/{$argv[1]}"))
    $path = "lib/generators/{$argv[1]}";
else
    die("Could not find generator {$argv[1]}.\n");

if ($argc > 2 && (in_array('--help', $argv) || in_array('-h', $argv)))
{
    include("{$path}/USAGE");
    die();
}

class Generator
{
    public function usage()
    {
        global $path;
        include("{$path}/USAGE");
    }

    public function create_file($name, $content)
    {
        $f = fopen(ROOT . $name, "w");
        fwrite($f, $content);
        fclose($f);
    }
}

include("{$path}/{$argv[1]}_generator.php");

$class = implode('', array_map(function($e)
{
    return ucfirst($e);
}, explode('_', $argv[1] . '_generator')));

$class = new $class;

$args = array_splice($argv, 2);
$class->generate($args);