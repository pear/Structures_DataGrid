<?php
$datagrid =& new Structures_DataGrid();

// Replace this with your database access informations:
$bindOptions['dsn'] = "mysql://foo:bar@host/world";

// The City table contains 5 fields: ID, Name, CountryCode, District and Population
$datagrid->bind("SELECT * FROM City ORDER BY Population", $bindOptions);

// We want to remove the ID field, so we retrieve a reference to the Column:
$column =& $datagrid->getColumnByField('ID');

// And we drop that column:
$datagrid->dropColumn($column);

// This will only render 4 fields: Name, CountryCode, District and Population:
$datagrid->render();
?>
