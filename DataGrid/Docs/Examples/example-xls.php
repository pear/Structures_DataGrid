<?php
require_once('Structures/DataGrid.php');

// Data to be printed by DataGrid
$rs = array(array('id' => 1,
                  'first_name' => 'Bob',
                  'last_name' => 'Smith',
                  'age' => '37'),
            array('id' => 2,
                  'first_name' => 'John',
                  'last_name' => 'Doe',
                  'age' => '23'),
            array('id' => 3,
                  'first_name' => 'Fred',
                  'last_name' => 'Thompson',
                  'age' => '58'),
            array('id' => 3,
                  'first_name' => 'Sally',
                  'last_name' => 'Robinson',
                  'age' => '52'),
            array('id' => 3,
                  'first_name' => 'Robert',
                  'last_name' => 'Brown',
                  'age' => '19'));

// Create DataGrid
$dg = new Structures_DataGrid(null, null, DATAGRID_RENDER_XLS);

// Define columns for the DataGrid
$column = new Structures_DataGrid_Column('Name', 'first_name');
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Age', 'age');
$dg->addColumn($column);


// Add rows to the DataGrid
foreach ($rs as $row) {
    $row = new Structures_DataGrid_Record($row);
    $result = $dg->addRecord($row);
    if (PEAR::isError($result)) {
        echo $result->getMessage();
    }
}

// Sort the array based on the field
if (isset($_GET['orderBy'])) {
    $dg->sortRecordSet($_GET['orderBy'], $_GET['direction']);
}

// Print the DataGrid
$dg->renderer->setFilename('datagrid.xls');
$dg->render();
?>
