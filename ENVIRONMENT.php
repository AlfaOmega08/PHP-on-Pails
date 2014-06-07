<?php

$env = getenv('PAILS_ENV');
if ($env === FALSE)
  $env = "development";

define('ENVIRONMENT', $env);
