<?php

// run me from the command line, using: php parse-options.php
error_reporting(E_ALL);

require_once 'File/Util.php';

// we do this only for Renderer drivers, maybe later also for DataSource drivers
define('PATH', '../../DataGrid/Renderer/');
define('TMP_PATH',  File_Util::tmpDir() . '/sdgdoc/');

if (!is_dir (TMP_PATH)) {
    mkdir(TMP_PATH, 0770, true);
}

// parse every file in the given path
$dir = dir(PATH);
while (false !== ($entry = $dir->read())) {

    $driver = substr($entry, 0, strrpos($entry, '.'));
    $extension = substr($entry, strrpos($entry, '.'));

    // parse only files with an .php extension
    if ($extension != '.php') {
        continue;
    }
    echo 'Parsing ' . $entry . ' ... ';

    // read the file contents
    // (using file() instead of file_get_contents() to avoid a complex regular
    // expression; the format is almost fixed, so using single lines is not a
    // problem here)
    $file = file(PATH . $entry);

    // search for the row after that the options are documented
    $startRow = getStartRow($file);

    // the driver has no options
    if ($startRow === false) {
        echo "NO OPTIONS FOUND\n";
        continue;
    }

    // search for the row that indicates the end of the options block
    $endRow = getEndRow($file, $startRow);

    // the driver has no options
    // (this should not happen => die)
    if ($endRow === false) {
        die('END OF OPTION BLOCK NOT FOUND');
    }

    // collect the options
    $options = getOptions($file, $startRow, $endRow);

    // save the options as an XML file
    writeXMLFile($driver, $options);
    
    // we're done with this file
    echo "DONE\n";
}

// close the directory handle
$dir->close();

function getStartRow($file) {
    $startRow = false;
    foreach ($file as $rowNumber => $row) {
        // we've found the row
        if (strpos($row, ' * SUPPORTED OPTIONS:') !== false) {
            $startRow = $rowNumber;
            break;
        }
    }

    return $startRow;
}

function getEndRow($file, $startRow) {
    $endRow = false;
    for ($i = $startRow + 2; $i < count($file); $i++) {
        if (trim($file[$i]) == '*') {
            $endRow = $i;
            break;
        }
    }

    return $endRow;
}

function getOptions($file, $startRow, $endRow) {
    $currOption = '';
    $options = array();
    for ($i = $startRow + 2; $i < $endRow; $i++) {

        // do we have a new option?
        if (substr($file[$i], 3, 1) == '-') {
            $res = preg_match('#- ([a-z]+):\s*\(([a-z]+)\)\s+(.*)#i', $file[$i], $matches);
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
        
        // okay, now default value, then we have to add it to the description
        $options[$currOption]['desc'] = wordwrap($options[$currOption]['desc'] . ' ' . $text);
    }

    return $options;
}

function indentMultiLine($content, $indentStr, $indentNum)
{
    $prefix = str_repeat($indentStr, $indentNum);
    $width = 80 - $indentNum - 1;
    $content = ereg_replace("[ \n]+", ' ', $content);
    $content = wordwrap($content, $width);
    return $prefix . trim(str_replace("\n", "\n$prefix$indentStr", $content));
}

function writeXMLFile($driver, $options) {
    $xml  = '<table>' . "\n";
    $xml .= ' <title>Options for this driver</title>' . "\n";
    $xml .= ' <tgroup cols="4">' . "\n";
    $xml .= '  <thead>' . "\n";
    $xml .= '   <row>' . "\n";
    $xml .= '    <entry>Option</entry>' . "\n";
    $xml .= '    <entry>Type</entry>' . "\n";
    $xml .= '    <entry>Description</entry>' . "\n";
    $xml .= '    <entry>Default Value</entry>' . "\n";
    $xml .= '   </row>' . "\n";
    $xml .= '  </thead>' . "\n";
    $xml .= '  <tbody>' . "\n";
    foreach ($options as $option => $details) {
      $xml .= '   <row>' . "\n";
      $xml .= '    <entry>' . $option . '</entry>' . "\n";
      $xml .= '    <entry>' . $details['type'] . '</entry>' . "\n";
      $xml .= indentMultiLine('<entry>' . $details['desc'] . '</entry>', ' ', 4) . "\n";
      $xml .= '    <entry>' . (isset($details['default']) ? $details['default'] : '') . '</entry>' . "\n";
      $xml .= '   </row>' . "\n";
    }
    $xml .= '  </tbody>' . "\n";
    $xml .= ' </tgroup>' . "\n";
    $xml .= '</table>' . "\n";
    file_put_contents(TMP_PATH . $driver . '.xml', $xml);
}

?>
