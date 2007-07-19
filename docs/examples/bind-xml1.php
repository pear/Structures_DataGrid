<?php
$xml = <<<XML
<records>
  <record>
    <firstname>Olivier</firstname>
    <lastname>Guilyardi</lastname>
    <city>Paris</city>
    <country>France</country>
  </record>
  <record>
    <firstname>Mark</firstname>
    <lastname>Wiesemann</lastname>
    <city>Aachen</city>
    <country>Germany</country>
  </record>
</records>
XML;

// Options for the bind() call (empty in this example)
$options = array();

// Bind the XML string
$test = $datagrid->bind($xml, $options, 'XML');

// Print binding error if any
if (PEAR::isError($test)) {
    echo $test->getMessage(); 
}
?>
