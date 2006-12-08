<?php

// Format UNIX timestamps as english dates:
$column->format('dateFromTimestamp', 'm/d/y');

// Format MySQL DATE, DATETIME or TIMESTAMP strings as french dates:
$column->format('dateFromMysql', 'd/m/y');

// Format numbers with 3 decimals and no thousands separator:
$column->format('number', 3, '.', '');

// Format numbers with 2 decimals:
$column->format('number', 2);

?>
