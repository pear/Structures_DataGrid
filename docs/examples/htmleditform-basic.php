<?php
require_once 'Structures/DataGrid.php';
$your_data = array();
$datagrid =& new Structures_DataGrid();
$datagrid->bind($your_data);  // bind your data here

$datagrid->setRenderer('HTMLEditForm');

$datagrid->render();
?>
