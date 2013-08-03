<?php

$template = "<?php

class {$migration_name} extends Migration
{
    function up()
    {
        \$this->create_table('{$table_name}', [], function(\$t)
        {\n";

foreach ($variables as $var => $type)
    $template .= "\t\t\t\$t->{$type}('{$var}');\n";

if ($with_timestamps)
    $template .= "\t\t\t\$t->timestamps();\n";

$template .= "
            return \$t;
        });
    }

    function down()
    {
        \$this->drop_table('{$table_name}');
    }
}";
