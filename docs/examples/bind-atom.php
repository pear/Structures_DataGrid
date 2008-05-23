<?php

require_once 'Structures/DataGrid.php';    

// Create the datagrid
$datagrid =& new Structures_DataGrid();

// Use XPath and namespace to extract Atom entries
$options = array(
    'namespaces' => array('atom' => 'http://www.w3.org/2005/Atom'),
    'path'       => '//atom:entry'
);
$feed = 'http://www.php.net/feed.atom';
$test = $datagrid->bind($feed, $options, 'XML');
if (PEAR::isError($test)) {
    exit($test->getMessage()); 
}

// Render the feed as a table
$datagrid->render();

?>
