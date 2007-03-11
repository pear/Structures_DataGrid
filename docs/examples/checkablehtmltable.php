<?php
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/QuickHtml.php';
require_once 'Structures/DataGrid.php';

// prepare the form and the QuickHtml renderer
$form =& new HTML_QuickForm();
$renderer =& new HTML_QuickForm_Renderer_QuickHtml();

// add action selectbox and submit button to the form
$form->addElement('select', 'action', 'choose',
                  array('delete' => 'Delete',
                        'move'   => 'Move to archive'));
$form->addElement('submit', 'submit', 'Save');

// prepare the DataGrid
$dg =& new Structures_DataGrid();
if (PEAR::isError($dg)) {
   die($dg->getMessage() . '<br />' . $dg->getDebugInfo());
}

// bind some data (e.g. via a SQL query and MDB2)
$error = $dg->bind('SELECT * FROM news',
                   array('dsn' => 'mysql://user:password@server/database'));
if (PEAR::isError($error)) {
   die($error->getMessage() . '<br />' . $error->getDebugInfo());
}

// the renderer adds an auto-generated column for the checkbox by default;
// it is also possible to add a column yourself, for example like in the
// following four lines:
$column = new Structures_DataGrid_Column('checkboxes', 'idList', null,
                                         array('width' => '10'));
$dg->addColumn($column);
$dg->generateColumns();

$rendererOptions = array('form'         => $form,
                         'formRenderer' => $renderer,
                         'inputName'    => 'idList',
                         'primaryKey'   => 'id'
                        );

// use a template string for the form
$tpl = '';

// generate the HTML table and add it to the template string
$tpl .= $dg->getOutput('CheckableHTMLTable', $rendererOptions);
if (PEAR::isError($tpl)) {
   die($tpl->getMessage() . '<br />' . $tpl->getDebugInfo());
}

// add the HTML code of the action selectbox and the submit button to the
// template string
$tpl .= $renderer->elementToHtml('action');
$tpl .= $renderer->elementToHtml('submit');

// we're now ready to output the form (toHtml() adds the <form> / </form>
// pair to the template)
echo $renderer->toHtml($tpl);

// if the form was submitted and the data is valid, show the submitted data
if ($form->isSubmitted() && $form->validate()) {
    var_dump($form->getSubmitValues());
}
?>