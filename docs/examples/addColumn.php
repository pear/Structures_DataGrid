<?php
$datagrid =& new Structures_DataGrid();
$column = new Structures_DataGrid_Column('Title', 'title', 'title', array('align' => 'center'), 'N/A', null);
$datagrid->addColumn($column);
?>
