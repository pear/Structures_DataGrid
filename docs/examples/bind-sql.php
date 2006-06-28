<?php
// Setup your database connection
$options = array('dsn' => 'mysql://user:password@host/db_name');

// Bind a basic SQL statement as datasource
// Note: ORDER BY and LIMIT clause are automatically added
$test = $datagrid->bind('SELECT * FROM my_table', $options);

// Print binding error if any
if (PEAR::isError($test)) {
    echo $test->getMessage(); 
}
?>
