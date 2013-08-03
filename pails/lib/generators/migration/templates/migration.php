<?php

$template = "<?php

class {$class_name} extends Migration
{
    function up()
    {
";

foreach ($variables as $var => $type)
    $template .= "\t\t\$this->add_column('{$table_name}', '{$var}', '{$type}');\n";

$template .=
"    }

    function down()
    {
";
foreach ($variables as $var => $type)
    $template .= "\t\t\$this->remove_column('{$table_name}', '{$var}');\n";

$template .=
"    }
}";
