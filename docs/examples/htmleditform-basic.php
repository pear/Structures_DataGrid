<?php
require_once 'Structures/DataGrid.php';

$datagrid =& new Structures_DataGrid();
$datagrid->bind(...);  // bind your data here

$datagrid->setRenderer('HTMLEditForm');

$datagrid->render();
?>