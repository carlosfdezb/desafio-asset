<?php

$path = realpath('database/database.sqlite');
$env = file_get_contents('.env');
$env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=' . $path, $env);
file_put_contents('.env', $env);
