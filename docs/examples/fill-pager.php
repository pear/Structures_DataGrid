<?php

require_once 'Pager/Pager.php';

// Create a Pager object with your own options
$pager =& Pager::factory($options);

// fill() sets the $pager object up, according to your data and settings
$datagrid->fill($pager);

// Render the paging links 
echo $pager->links;

// Or a select field if you like that
echo $pager->getpageselectbox();

?>
