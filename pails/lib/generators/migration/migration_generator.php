<?php

include(ROOT . '/pails/Utilities.php');

class MigrationGenerator extends Generator
{
    public function generate()
    {
        $args = func_get_args()[0];
        if (count($args) < 1)
        {
            $this->usage();
            return;
        }

        $migration_name = $args[0];
        $migration_exploded = explode('_', $migration_name);

        if (($pos = array_search('to', $migration_exploded)) !== false)
            $table_name = implode('_', array_splice($migration_exploded, $pos + 1));
        else
            $table_name = "";

        $variables = [];

        for ($i = 1; isset($args[$i]) && substr($args[$i], 0, 2) != '--'; $i++)
        {
            list($var_name, $type) = explode(':', $args[$i]);
            $variables[$var_name] = $type;
        }

        $with_timestamps = true;
        $generate_migration = true;
        foreach ($args as $arg)
        {
            if ($arg == '--timestamps=false')
                $with_timestamps = false;
            if ($arg == '--migration=false')
                $generate_migration = false;
        }

        if ($generate_migration)
        {
            $class_name = pascalize($migration_name);
            include('templates/migration.php');
            $timestamp = strftime("%Y%m%d%H%M%S");
            $this->create_file("/db/migrations/{$timestamp}_{$migration_name}.php", $template);
        }
    }
}
