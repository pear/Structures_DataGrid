<?php

// this script is intended to be run by mkmanual.sh

error_reporting(E_ALL);

if ($argc != 2) {
    die('Missing parameter: temporary target dir');
}

define('PATH', '../');
define('TMP_PATH', $argv[1] . '/structures/structures-datagrid/');

if (!is_dir(TMP_PATH)) {
    mkdir(TMP_PATH, 0770, true);
}
if (!is_dir(TMP_PATH . 'structures-datagrid-datasource/')) {
    mkdir(TMP_PATH . 'structures-datagrid-datasource/', 0770, true);
}
if (!is_dir(TMP_PATH . 'structures-datagrid-renderer/')) {
    mkdir(TMP_PATH . 'structures-datagrid-renderer/', 0770, true);
}

$descriptions = array();
$options = array();
$notes = array();
$inheritance = array();

// parse all directories whose names begin with 'Structures_DataGrid'
$directories = scandir(PATH);
foreach ($directories as $directory) {
    if (substr($directory, 0, 19) == 'Structures_DataGrid') {
        parseDirectory($descriptions, $options, $notes, $inheritance, $directory);
    }
}

$ids = array();

// loop over the inheritance array to store the (own and inherited) options of
// all drivers
foreach ($inheritance as $class => $extends) {
    // ignore classes that don't extend other classes because they
    // - either have no options (e.g. DataGrid.php, Column.php)
    // - or should not occur with options in the manual (e.g. DataSource.php)
    if (is_null($extends)) {
        continue;
    }
    // save the class name
    $orig_class = $class;
    // sum up the optionx for the current driver; driver's own options override
    // general options from extended classes
    $driver_options = $options[$class];
    $extends_rel = $inheritance[$class];
    while (!is_null($extends_rel)) {
        $class = $extends_rel;
        $extends_rel = $inheritance[$class];
        $driver_options = array_merge($options[$class], $driver_options);
    }
    // sort the options alphabetically
    ksort($driver_options);
    // save the options as an XML file
    $id = writeXMLFile($orig_class, $descriptions[$orig_class], $driver_options, $notes[$orig_class]);
    $ids[] = $id;
}

// write all IDs into a temporary file (contents need to be manually copied!)
$id_file = '';
foreach ($ids as $id) {
    $id_file .= '&' . $id . ";\n";
}
file_put_contents(TMP_PATH . 'ids.txt', $id_file);

function parseDirectory(&$descriptions, &$options, &$notes, &$inheritance, $dir)
{
    $entries = scandir(PATH . $dir);
    foreach ($entries as $entry) {
        // ignore pointers to current and parent directory
        // ignore CVS, documentation and tools directories
        if (!in_array($entry, array('.', '..', 'CVS', 'docs', 'tools'))) {
            // step recursive into subdirectories
            if (is_dir(PATH . $dir . '/' . $entry)) {
                parseDirectory($descriptions, $options, $notes, $inheritance, $dir . '/' . $entry);
            }
            // parse the file if the extension is .php
            if (substr($entry, -4) == '.php') {
                parseFile($descriptions, $options, $notes, $inheritance, $dir . '/' . $entry);
            }
        }
    }
}

function parseFile(&$descriptions, &$options, &$notes, &$inheritance, $filename)
{
    echo 'Parsing ' . $filename . ' ... ';

    // read the file contents
    // (using file() instead of file_get_contents() to avoid a complex regular
    // expression; the format is almost fixed, so using single lines is not a
    // problem here)
    $file = file(PATH . $filename);

    // get the class name and the name of the extended class
    list($class, $extends) = getClassName($file);

    // save the inheritance relation
    $inheritance[$class] = $extends;

    // get the descriptions
    $descriptions[$class] = getDescriptions($file, $descriptionsEndRow);

    // get the options
    $options[$class] = getOptions($file, $descriptionsEndRow, $optionsEndRow);

    // get the 'GENERAL NOTES'
    $notes[$class] = getNotes($file, $optionsEndRow);

    // we're done with this file
    echo "DONE\n";
}

function getDescriptionsStartRow($file)
{
    $startRow = false;
    $i = 0;
    foreach ($file as $rowNumber => $row) {
        // we've found the row where the descriptions begin
        if ($i > 2 && strpos($row, '/**') !== false) {
            $startRow = $rowNumber;
            break;
        }
        $i++;
    }

    return $startRow;
}

function getDescriptionsEndRow($file, $startRow)
{
    if ($startRow === false) {
        return false;
    }
    
    $endRowTemp1 = 0;
    for ($i = $startRow + 2; $i < count($file); $i++) {
        // we've found one possible end of the descriptions
        if (strpos($file[$i], 'SUPPORTED OPTIONS:') !== false) {
            $endRowTemp1 = $i - 1;
            break;
        }
    }
    // maybe there are no options available
    // ==> we also search for 'SUPPORTED OPERATION MODES'
    $endRowTemp2 = 0;
    for ($i = $startRow + 2; $i < count($file); $i++) {
        // we've found another possible end of the descriptions
        if (strpos($file[$i], 'SUPPORTED OPERATION MODES:') !== false) {
            $endRowTemp2 = $i - 1;
            break;
        }
    }
    // maybe there are no also no operation modes available (that's the case in
    // DataSource drivers)
    // ==> we also search for '@version'
    $endRowTemp3 = 0;
    for ($i = $startRow + 2; $i < count($file); $i++) {
        // we've found another possible end of the descriptions
        if (strpos($file[$i], '@version') !== false) {
            $endRowTemp3 = $i - 1;
            break;
        }
    }

    // TODO: maybe the following checks can be formulated shorter?
    if ($endRowTemp1 > 0 && $endRowTemp2 > 0 && $endRowTemp3 > 0) {
        return min($endRowTemp1, $endRowTemp2, $endRowTemp3);
    } elseif ($endRowTemp1 > 0 && $endRowTemp2 > 0) {
        return min($endRowTemp1, $endRowTemp2);
    } elseif ($endRowTemp1 > 0 && $endRowTemp3 > 0) {
        return min($endRowTemp1, $endRowTemp3);
    } elseif ($endRowTemp2 > 0 && $endRowTemp3 > 0) {
        return min($endRowTemp2, $endRowTemp3);
    } elseif ($endRowTemp1 > 0) {
        return $endRowTemp1;
    } elseif ($endRowTemp2 > 0) {
        return $endRowTemp2;
    } elseif ($endRowTemp3 > 0) {
        return $endRowTemp3;
    }
    return false;
}

function getDescriptions($file, &$descriptionsEndRow)
{
    // search for the limits of the descriptions
    $startRow = getDescriptionsStartRow($file);
    $endRow = getDescriptionsEndRow($file, $startRow);

    $descriptionsEndRow = $endRow;

    // the driver has no options
    if ($startRow === false || $endRow === false) {
        echo "NO DESCRIPTION FOUND\n";
        return array('short' => '', 'long' => '');
    }

    // read the descriptions
    $short = '';
    $long = '';
    for ($i = $startRow + 1; $i < $endRow; $i++) {
        $row = rtrim(substr($file[$i], 3));
        // do we have found the end of the short description?
        if ($row == '') {
            break;
        }
        $short .= '   ' . $row . "\n";
    }
    for ($j = $i + 1; $j < $endRow; $j++) {
        $long .= '   ' . rtrim(substr($file[$j], 3)) . "\n";
    }

    return array('short' => trim($short), 'long' => trim($long));
}

function getOptionsStartRow($file, $descriptionsEndRow)
{
    if (strpos($file[$descriptionsEndRow + 1], ' * SUPPORTED OPTIONS:') !== false) {
        return $descriptionsEndRow + 1;
    }
    return false;

}

function getOptionsEndRow($file, $startRow)
{
    $endRow = false;
    for ($i = $startRow + 2; $i < count($file); $i++) {
        // we've found the row where the options documentation ends
        if (trim($file[$i]) == '*') {
            $endRow = $i;
            break;
        }
    }

    return $endRow;
}

function getOptions($file, $descriptionsEndRow, &$optionsEndRow)
{
    // search for the row after that the options are documented
    $startRow = getOptionsStartRow($file, $descriptionsEndRow);

    // the driver has no options
    if ($startRow === false) {
        echo "NO OPTIONS FOUND\n";
        return array();
    }

    // search for the row that indicates the end of the options block
    $endRow = getOptionsEndRow($file, $startRow);

    // the driver has no options
    // (this should not happen => die)
    if ($endRow === false) {
        die('END OF OPTION BLOCK NOT FOUND');
    }

    $optionsEndRow = $endRow;

    // collect the options
    return _getOptions($file, $startRow, $endRow);
}

function _getOptions($file, $startRow, $endRow)
{
    $currOption = '';
    $options = array();
    for ($i = $startRow + 2; $i < $endRow; $i++) {

        // do we have a new option?
        if (substr($file[$i], 3, 1) == '-') {
            $res = preg_match('#- ([a-z_]+):\s*\(([a-z]+)\)\s+(.*)#i', $file[$i], $matches);
            // check whether the regular expression matched
            // (if not: die, this should not happen)
            if ($res !== 1) {
                die('REGEXP DID NOT MATCH IN LINE ' . $i);
            }
            $currOption = $matches[1];
            $options[$currOption] = array('type' => $matches[2],
                                          'desc' => trim($matches[3])
                                         );
            continue;
        }

        // no, we'll stick with the last option
        $text = trim(substr($file[$i], 2));
        
        // but maybe we have also found the default value
        if (preg_match('#\(default: (.*)\)#', $text, $matches)) {
            $options[$currOption]['default'] = $matches[1];
            continue;
        }
        
        // okay, no default value, then we have to add it to the description
        $options[$currOption]['desc'] = wordwrap($options[$currOption]['desc'] . ' ' . $text);
    }

    return $options;
}

function getNotesStartRow($file, $optionsEndRow)
{
    // for DataSource drivers this is the expected place
    if (strpos($file[$optionsEndRow + 1], ' * GENERAL NOTES:') !== false) {
        return $optionsEndRow + 1;
    }
    // for Renderer drivers this is the expected place
    if (strpos($file[$optionsEndRow + 7], ' * GENERAL NOTES:') !== false) {
        return $optionsEndRow + 7;
    }
    return false;
}

function getNotesEndRow($file, $startRow)
{
    $endRow = false;
    if ($startRow === false) {
        return $endRow;
    }
    for ($i = $startRow + 2; $i < count($file); $i++) {
        // we've found the row where the 'GENERAL NOTES' documentation ends
        if (strpos($file[$i], '@version') !== false) {
            $endRow = $i - 1;
            break;
        }
    }

    return $endRow;
}

function getNotes($file, $optionsEndRow)
{
    // search for the limits of the 'GENERAL NOTES' section
    $startRow = getNotesStartRow($file, $optionsEndRow);
    $endRow = getNotesEndRow($file, $startRow);

    // the driver has no options
    if ($startRow === false || $endRow === false) {
        echo "NO NOTES FOUND\n";
        return '';
    }

    // read the 'GENERAL NOTES'
    $notes = '';
    for ($i = $startRow + 2; $i < $endRow; $i++) {
        $row = rtrim(substr($file[$i], 3));
        if (strpos($row, '<code>') !== false) {
            $codeTagOpen = true;
        }
        if (strpos($row, '</code>') !== false) {
            $codeTagOpen = false;
        }
        if (!$codeTagOpen && $row == '') {
            $notes .= "  </para>\n  <para>";
        }
        $notes .= '   ' . $row . "\n";
    }

    $notes = htmlentities(trim($notes));
    $notes = str_replace(array('&lt;code&gt;', '&lt;/code&gt;', '&lt;para&gt;', '&lt;/para&gt;'),
                         array('<programlisting>', '</programlisting>', '<para>', '</para>'),
                         $notes
                        );
    return $notes;
}

function getClassName($file)
{
    $file = join("\n", $file);
    if (preg_match('#class ([a-z0-9_]+)\s+(extends\s+([a-z0-9_]+)\s+)?\{#im', $file, $matches)) {
        $class = $matches[1];
        $extends = null;
        if (array_key_exists(3, $matches)) {
            $extends = $matches[3];
        }
        return array($class, $extends);
    }
    die('CLASS NAME NOT FOUND');
}

function indentMultiLine($content, $indentStr, $indentNum)
{
    $prefix = str_repeat($indentStr, $indentNum);
    $width = 80 - $indentNum - 1;
    $content = ereg_replace("[ \n]+", ' ', $content);
    $content = wordwrap($content, $width);
    return $prefix . trim(str_replace("\n", "\n$prefix$indentStr", $content));
}

function writeXMLFile($driver, $descriptions, $options, $notes)
{
    // prepare some variables for the XML contents
    $type = 'structures-datagrid-' . ((strpos($driver, 'DataSource') !== false) ? 'datasource' : 'renderer');
    $name = strtolower(substr($driver, strrpos($driver, '_') + 1));
    $id = 'package.structures.structures-datagrid.' . $type . '.' . $name;

    // prepare the XML file
    $xml  = '<?xml version="1.0" encoding="iso-8859-1" ?>' . "\n";
    $xml .= '<!-- $' . 'Revision$ -->' . "\n";  // avoid replacement by CVS here
    $xml .= '<refentry id="' . $id . '">' . "\n";
    $xml .= ' <refnamediv>' . "\n";
    $xml .= '  <refname>' . $driver . '</refname>' . "\n";
    $xml .= '  <refpurpose>' . htmlentities($descriptions['short']) . '</refpurpose>' . "\n";
    $xml .= ' </refnamediv>' . "\n";
    // TODO: extract example code link from the source code
    if ($descriptions['long'] != '') {
        $xml .= ' <refsect1 id="' . $id . '.desc">' . "\n";
        $xml .= '  <title>Description</title>' . "\n";
        $xml .= '  <para>' . "\n";
        $xml .= '   ' . htmlentities($descriptions['long']) . "\n";
        $xml .= '  </para>' . "\n";
        $xml .= ' </refsect1>' . "\n";
    }
    $xml .= ' <refsect1 id="' . $id . '.options">' . "\n";
    $xml .= '  <title>Options</title>' . "\n";
    $xml .= '  <para>' . "\n";
    $xml .= '   This driver accepts the following options:' . "\n";
    $xml .= '  </para>' . "\n";
    $xml .= '  <table>' . "\n";
    $xml .= '   <title>Options for this driver</title>' . "\n";
    $xml .= '   <tgroup cols="4">' . "\n";
    $xml .= '    <thead>' . "\n";
    $xml .= '     <row>' . "\n";
    $xml .= '      <entry>Option</entry>' . "\n";
    $xml .= '      <entry>Type</entry>' . "\n";
    $xml .= '      <entry>Description</entry>' . "\n";
    $xml .= '      <entry>Default Value</entry>' . "\n";
    $xml .= '     </row>' . "\n";
    $xml .= '    </thead>' . "\n";
    $xml .= '    <tbody>' . "\n";
    foreach ($options as $option => $details) {
      $xml .= '     <row>' . "\n";
      $xml .= '      <entry>' . htmlentities($option) . '</entry>' . "\n";
      $xml .= '      <entry>' . htmlentities($details['type']) . '</entry>' . "\n";
      $xml .= indentMultiLine('<entry>' . htmlentities($details['desc']) . '</entry>', ' ', 6) . "\n";
      $xml .= '      <entry>' . (isset($details['default']) ? htmlentities($details['default']) : '') . '</entry>' . "\n";
      $xml .= '     </row>' . "\n";
    }
    $xml .= '    </tbody>' . "\n";
    $xml .= '   </tgroup>' . "\n";
    $xml .= '  </table>' . "\n";
    $xml .= ' </refsect1>' . "\n";
    if ($notes != '') {
        $xml .= ' <refsect1 id="' . $id . '.notes">' . "\n";
        $xml .= '  <title>General notes</title>' . "\n";
        $xml .= '  <para>' . "\n";
        $xml .= '   ' . $notes . "\n";
        $xml .= '  </para>' . "\n";
        $xml .= ' </refsect1>' . "\n";
    }
    $xml .= '</refentry>' . "\n";
    $xml .= '<!-- Keep this comment at the end of the file' . "\n";
    $xml .= 'Local variables:' . "\n";
    $xml .= 'mode: sgml' . "\n";
    $xml .= 'sgml-omittag:t' . "\n";
    $xml .= 'sgml-shorttag:t' . "\n";
    $xml .= 'sgml-minimize-attributes:nil' . "\n";
    $xml .= 'sgml-always-quote-attributes:t' . "\n";
    $xml .= 'sgml-indent-step:1' . "\n";
    $xml .= 'sgml-indent-data:t' . "\n";
    $xml .= 'sgml-parent-document:nil' . "\n";
    $xml .= 'sgml-default-dtd-file:"../../../../../manual.ced"' . "\n";
    $xml .= 'sgml-exposed-tags:nil' . "\n";
    $xml .= 'sgml-local-catalogs:nil' . "\n";
    $xml .= 'sgml-local-ecat-files:nil' . "\n";
    $xml .= 'End:' . "\n";
    $xml .= 'vim600: syn=xml fen fdm=syntax fdl=2 si' . "\n";
    $xml .= 'vim: et tw=78 syn=sgml' . "\n";
    $xml .= 'vi: ts=1 sw=1' . "\n";
    $xml .= '-->' . "\n";

    // write the XML file
    file_put_contents(TMP_PATH . $type . '/' . $name . '.xml', $xml);

    // return the generated id
    return $id;
}

?>
