<?php 
require_once 'Structures/DataGrid.php';    

$datagrid =& new Structures_DataGrid(10);
$options = array('dsn' => 'mysql://username@localhost/mydatabase');
$datagrid->bind("SELECT * FROM mytable", $options);

header('Content-type: application/vnd.mozilla.xul+xml'); 
 
echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
echo "<?xml-stylesheet href=\"myStyle.css\" type=\"text/css\"?>\n";

echo "<window title=\"MyDataGrid\" 
       xmlns=\"http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul\">\n";
       
$datagrid->render('XUL');

echo "</window>\n";
?> 
