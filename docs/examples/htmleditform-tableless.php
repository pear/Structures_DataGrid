<?php
// don't forget to include the stylesheet to get a reasonable layout

require_once 'Structures/DataGrid.php';
require_once 'HTML/QuickForm/DHTMLRulesTableless.php';
require_once 'HTML/QuickForm/Renderer/Tableless.php';

$datagrid =& new Structures_DataGrid();
$datagrid->bind(...);  // bind your data here

// create the form object, using DHTMLRules
$form = new HTML_QuickForm_DHTMLRulesTableless('editform', null, null,
                                               null, null, true);
$form->removeAttribute('name');  // for XHTML validity

// to get a legend for the fieldset, we add a header element
$form->addElement('header', 'header', 'EditForm example');

// fill() makes the renderer to generate the needed form elements
$datagrid->fill($form, null, 'HTMLEditForm');

// we have to add a submit button ourselves
$form->addElement('submit', null, 'Submit');

// to show the DHTMLRules functionality, we add a required rule
// (we assume that there is an element with name 'id')
$form->addRule('id', 'Please enter the ID.', 'required', null, 'client');

// to get validation onChange/onBlur events, we need the following call
$form->getValidationScript();

// instantiate the tableless renderer and output the form
$renderer =& new HTML_QuickForm_Renderer_Tableless();
$form->accept($renderer);
echo $renderer->toHtml();
?>