<?php

include(ROOT . '/pails/Utilities.php');

class ModelGenerator extends Generator
{
    public function generate()
    {
        $args = func_get_args()[0];
        if (count($args) < 1)
        {
            $this->usage();
            return;
        }

        $table_name = plural($args[0]);

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
            $migration_name = pascalize("add_{$table_name}");

            include('templates/migration.php');
            $timestamp = strftime("%Y%m%d%H%M%S");
            $this->create_file("/db/migrations/{$timestamp}_add_{$table_name}.php", $template);
        }

        $model_name = pascalize($args[0]);

        include('templates/model.php');
        $this->create_file("/app/models/{$args[0]}.php", $template);
    }
}
