<?php
$person = new DataObjects_Person;

$person->hair = 'red';
$person->has_glasses = 1; 

$datagrid->bind($person);
?>
