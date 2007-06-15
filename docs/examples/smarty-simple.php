<?php
require_once 'Structures/DataGrid.php';    
require_once 'Smarty.class.php';

$datagrid =& new Structures_DataGrid(10);
$options = array('dsn' => 'mysql://username@localhost/mydatabase');
$datagrid->bind("SELECT * FROM mytable", $options);

$smarty = new Smarty();
$datagrid->fill($smarty);
$smarty->display('smarty-simple.tpl');
?>
