<?php

// identical, for example /page/5/foo/ASC
$datagrid->setUrlFormat('/page/:page/:orderBy/:direction');
$datagrid->setUrlFormat('/:page/:orderBy/:direction', 'page');

// without /page, for example /5/foo/ASC
$datagrid->setUrlFormat('/:page/:orderBy/:direction');

// without paging, for example /sort/foo/ASC
$datagrid->setUrlFormat('/:orderBy/:direction', 'sort');

// with scriptname, for example /index.php/5/foo/ASC
$datagrid->setUrlFormat('/:page/:orderBy/:direction', 'page', 'index.php');

?>
