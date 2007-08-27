<?php
require_once 'PEAR.php';
require_once 'Structures/DataGrid.php';    

$datagrid =& new Structures_DataGrid(10);

$options['dsn'] = 'mysql://username@localhost/mydatabase';
$datagrid->bind("SELECT * FROM mytable", $options);

// Set the javascript handler function for onclick events
$datagrid->setRendererOption('jsHandler', 'updateGrid', true);

if (isset($_GET['ajax'])) {
    // Handle table AJAX requests 
    if ($_GET['ajax'] == 'table') {
        $datagrid->render();
    }
    // Handle pager AJAX requests 
    if ($_GET['ajax'] == 'pager') {
        $datagrid->render('Pager');
    }
    exit();
}

// No AJAX request, render the initial content..
?>
<html>

<head>
<!-- Require the Prototype JS framework from http://www.prototypejs.org -->
<script type="text/javascript" src="prototype.js"></script>
<script type="text/javascript">
function updateGrid(info) 
{
    var url = '<?php echo $_SERVER['PHP_SELF']; ?>';
    var pars = 'page=' + info.page;
    if (info.sort.length > 0) {
        pars += '&orderBy=' + info.sort[0].field + '&direction=' + info.sort[0].direction;
    }
        
    new Ajax.Updater( 'grid', url, { method: 'get', parameters: pars + '&ajax=table' });
    new Ajax.Updater( 'pager', url, { method: 'get', parameters: pars + '&ajax=pager' });

    // Important: return false to avoid href links
    return false;
}
</script>
</head>

<body>
Pages: <span id="pager"><?php $datagrid->render('Pager'); ?></span>
<div id="grid"><?php $datagrid->render(); ?></div>
</body>

</html>
