<html>
<head>
  <title>DataGrid: Example</title>
  <style type="text/css">
    table.datagrid {font-family: Verdana, Arial; font-size: x-small; background-color: #333333;}
  </style>
</head>
<body>

<?php
require_once('Structures/DataGrid.php');

function printLink($params)
{
    $id = $params['id'];

    return "<a href=\"edit_user.php?id=$id\">Edit</a>";
}

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
            array('id' => 4,
                  'first_name' => 'Sally',
                  'last_name' => 'Robinson',
                  'age' => '52'),
            array('id' => 5,
                  'first_name' => 'Robert',
                  'last_name' => 'Brown',
                  'age' => '19'));

// Define New DataGrid with a limit of 3 records
if (isset($_GET['page'])) {
    $dg = new Structures_DataGrid(4, $_GET['page']);
} else {
    $dg = new Structures_DataGrid(4);
}

// Define DataGrid Color Attributes
$dg->renderer->setTableHeaderBgColor('#3399FF');
$dg->renderer->setTableRowDarkBgColor('#CCCCCC');
$dg->renderer->setTableRowLightBgColor('#EEEEEE');

// Define DataGrid Table Attributes
$dg->renderer->setTableAttribute('width', '50%');
$dg->renderer->setTableAttribute('cellspacing', '1');
$dg->renderer->setTableAttribute('cellpadding', '4');
$dg->renderer->setTableAttribute('class', 'datagrid');

// Set empty row table attributes
// Clean me!
$dg->renderer->allowEmptyRows = true;
$dg->renderer->emptyRowAttributes = array('bgcolor' => '#FFFFFF');


// Define columns for the DataGrid
$column = new Structures_DataGrid_Column('Name', 'first_name', 'first_name',
                                  array('width' => '75%'));
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Age', 'age', 'age', array('width' => '25%'));
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Edit', null, null, array('align' => 'center'), null, null, 'printLink()');
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Delete', null, null, array('align' => 'center'));
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
echo $dg->render();
echo $dg->renderer->getPaging();
?>

<p>
  View as:
  <b>HTML Table</b> |
  <a href="example-dump.php">Debug View</a> |
  <a href="example-xls.php">Excel Spreadsheet</a> |
  <a href="example-xml.php">XML Document</a> |
  <a href="example-smarty.php">Smarty Template</a>
</p>

</body>
</html>
