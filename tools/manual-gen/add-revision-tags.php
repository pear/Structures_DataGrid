<?php

// this script is intended to be run by mkmanual.sh

error_reporting(E_ALL);

if ($argc != 2) {
    die('Missing parameter: temporary target dir' . "\n");
}

$directories = array($argv[1] . '/structures/structures-datagrid/structures-datagrid/',
                     $argv[1] . '/structures/structures-datagrid/structures-datagrid-column/'
                    );

foreach ($directories as $directory) {
    $files = scandir($directory);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        prepend_tag_to_file($directory . $file);
    }
}

function prepend_tag_to_file($file) {
    $contents  = '<?xml version="1.0" encoding="iso-8859-1" ?>' . "\n";
    $contents .= '<!-- $' . 'Revision$ -->' . "\n";  // avoid replacement by CVS here
    $contents .= file_get_contents($file);
    file_put_contents($file, $contents);
}

?>
