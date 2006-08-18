<?php

// run me from the command line (while being in pear/Structures_DataGrid), using:
//   php tools/manual-gen/parse-options.php

error_reporting(E_ALL);

require_once 'File/Util.php';

// TODO: this script should be called by mkmanual.sh
// TODO: pass the peardoc path as a parameter to this script
// TODO: we need different peardoc path definitions for Cygwin and PHP
// TODO: remove this constant
define('PEARDOC_PATH', 'c:/data/repository/peardoc');

define('PATH', '../');
define('TMP_PATH', File_Util::tmpDir() . '/sdgdoc/');
if (!is_dir(TMP_PATH)) {
    mkdir(TMP_PATH, 0770, true);
    mkdir(TMP_PATH . 'structures-datagrid-datasource/', 0770, true);
    mkdir(TMP_PATH . 'structures-datagrid-renderer/', 0770, true);
}

$options = array();
$inheritance = array();

// parse all directories whose names begin with 'Structures_DataGrid'
$directories = scandir(PATH);
foreach ($directories as $directory) {
    if (substr($directory, 0, 19) == 'Structures_DataGrid') {
        parseDirectory($options, $inheritance, $directory);
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
    $id = writeXMLFile($orig_class, $driver_options);
    $ids[] = $id;
}

// write all IDs into a temporary file (contents need to be manually copied!)
$id_file = '';
foreach ($ids as $id) {
    $id_file .= '&' . $id . ";\n";
}
file_put_contents(TMP_PATH . 'ids.txt', $id_file);

function parseDirectory(&$options, &$inheritance, $dir)
{
    $entries = scandir(PATH . $dir);
    foreach ($entries as $entry) {
        // ignore pointers to current and parent directory
        // ignore CVS, documentation and tools directories
        if (!in_array($entry, array('.', '..', 'CVS', 'docs', 'tools'))) {
            // step recursive into subdirectories
            if (is_dir(PATH . $dir . '/' . $entry)) {
                parseDirectory($options, $inheritance, $dir . '/' . $entry);
            }
            // parse the file if the extension is .php
            if (substr($entry, -4) == '.php') {
                parseFile($options, $inheritance, $dir . '/' . $entry);
            }
        }
    }
}

function parseFile(&$options, &$inheritance, $filename)
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

    // search for the row after that the options are documented
    $startRow = getStartRow($file);

    // the driver has no options
    if ($startRow === false) {
        echo "NO OPTIONS FOUND\n";
        $options[$class] = array();
        return;
    }

    // search for the row that indicates the end of the options block
    $endRow = getEndRow($file, $startRow);

    // the driver has no options
    // (this should not happen => die)
    if ($endRow === false) {
        die('END OF OPTION BLOCK NOT FOUND');
    }

    // collect the options
    $options[$class] = getOptions($file, $startRow, $endRow);
    
    // we're done with this file
    echo "DONE\n";
}

function getStartRow($file)
{
    $startRow = false;
    foreach ($file as $rowNumber => $row) {
        // we've found the row where the options documentation begins
        if (strpos($row, ' * SUPPORTED OPTIONS:') !== false) {
            $startRow = $rowNumber;
            break;
        }
    }

    return $startRow;
}

function getEndRow($file, $startRow)
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

function getOptions($file, $startRow, $endRow)
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

function writeXMLFile($driver, $options)
{
    // prepare some variables for the XML contents
    $type = 'structures-datagrid-' . ((strpos($driver, 'DataSource') !== false) ? 'datasource' : 'renderer');
    $name = strtolower(substr($driver, strrpos($driver, '_') + 1));
    $id = 'package.structures.structures-datagrid.' . $type . '.' . $name;

    // prepare the XML file
    $xml  = '<?xml version="1.0" encoding="iso-8859-1" ?>' . "\n";
    $xml .= '<!-- $Revision$ -->' . "\n";
    $xml .= '<refentry id="' . $id . '">' . "\n";
    $xml .= ' <refnamediv>' . "\n";
    $xml .= '  <refname>' . $driver . '</refname>' . "\n";
    // TODO: extract a short description from the source code
    $xml .= '  <refpurpose>[TODO]</refpurpose>' . "\n";
    $xml .= ' </refnamediv>' . "\n";
    // TODO: extract the 'GENERAL NOTES' section from the source code
    // TODO: extract example code link from the source code
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
      $xml .= '      <entry>' . $option . '</entry>' . "\n";
      $xml .= '      <entry>' . $details['type'] . '</entry>' . "\n";
      $xml .= indentMultiLine('<entry>' . $details['desc'] . '</entry>', ' ', 6) . "\n";
      $xml .= '      <entry>' . (isset($details['default']) ? $details['default'] : '') . '</entry>' . "\n";
      $xml .= '     </row>' . "\n";
    }
    $xml .= '    </tbody>' . "\n";
    $xml .= '   </tgroup>' . "\n";
    $xml .= '  </table>' . "\n";
    $xml .= ' </refsect1>' . "\n";
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
    
    // move the XML file into peardoc directory
    // TODO: this resets the CVS $Revision$ tag: every run of this script would
    //       indicate a change of *all* generated XML files
    //       solution: use 'patch' as in mkmanual.sh
    unlink(PEARDOC_PATH . '/en/package/structures/structures-datagrid/' . $type . '/' . $name . '.xml');
    rename(TMP_PATH . $type . '/' . $name . '.xml',
           PEARDOC_PATH . '/en/package/structures/structures-datagrid/' . $type . '/' . $name . '.xml');

    // return the generated id
    return $id;
}

?>
