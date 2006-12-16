<?php
require_once 'HTML/QuickForm.php';

// Create an empty form with your settings
$form = new HTML_QuickForm('myForm', 'POST');

// Customize it, add a header, text field, etc..
$form->addElement('header', null, 'Search & Sort Form Example');
$form->addElement('text', 'my_search', 'Search for:');

// Let the datagrid add sort fields, radio style
$options = array('directionStyle' => 'radio');
$datagrid->fill($form, $options, 'HTMLSortForm');

// You must add a submit button. fill() never does this
$form->addElement('submit', null, 'Submit');

// Use the native HTML_QuickForm::display() to print your form
$form->display();

?>
