<?php

// Format UNIX timestamps as english dates:
$column->format('dateFromTimestamp', 'm/d/y');

// Format MySQL DATE, DATETIME or TIMESTAMP strings as french dates:
$column->format('dateFromMysql', 'd/m/y');

// Format numbers with 3 decimals and no thousands separator:
$column->format('number', 3, '.', '');

// Format numbers with 2 decimals:
$column->format('number', 2);

// Format using a printf expression:
$column->format('printf', 'Number of potatoes: %d');

// Format an HTML link using a printf expression with url-encoding:
$column->format('printfURL', '<a href="edit.php?key=%s">Edit</a>'); 

?>
