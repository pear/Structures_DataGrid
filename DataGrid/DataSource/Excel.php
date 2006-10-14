<?php
/**
 * Excel Spreadsheet Data Source Driver
 * 
 * <pre>
 * +----------------------------------------------------------------------+
 * | PHP version 4                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2005 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | This source file is subject to version 2.0 of the PHP license,       |
 * | that is bundled with this package in the file LICENSE, and is        |
 * | available through the world-wide-web at                              |
 * | http://www.php.net/license/2_02.txt.                                 |
 * | If you did not receive a copy of the PHP license and are unable to   |
 * | obtain it through the world-wide-web, please send a note to          |
 * | license@php.net so we can mail you a copy immediately.               |
 * +----------------------------------------------------------------------+
 * | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
 * |          Olivier Guilyardi <olivier@samalyse.com>                    |
 * |          Mark Wiesemann <wiesemann@php.net>                          |
 * +----------------------------------------------------------------------+
 * </pre>
 *
 * Excel file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_DataSource_Excel
 * @category Structures
 */

require_once 'Structures/DataGrid/DataSource/Array.php';
require_once 'Spreadsheet/Excel/reader.php';

/**
 * Excel Spreadsheet Data Source Driver
 *
 * This class is a data source driver for an Excel spreadsheet.
 *
 * SUPPORTED OPTIONS:
 *
 * - header:     (bool)    Whether the Excel file contains a header row
 *                         (default: false)
 * 
 * GENERAL NOTES:
 *
 * This class expects the file reader.php in the directory Spreadsheet/Excel/.
 * 
 * Please note that the current version (2i) of Spreadsheet_Excel_Reader contains
 * a die() statement in the read() method in  reader.php (line 171). This makes
 * a reasonable PEAR error handling for the "file not found" error impossible.
 * 
 * It is therefore recommended that you replace the die() statement by something
 * like this:
 * <code>
 * return PEAR::raiseError('The filename ' . $sFileName . ' is not readable');
 * </code>
 * This class is optimized for the changed code (but will work also with the
 * die() in the reader class, of course) and also then a reasonable error
 * handling.
 * 
 * @version  $Revision$
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_DataSource_Excel
 * @category Structures
 */
class Structures_DataGrid_DataSource_Excel extends
    Structures_DataGrid_DataSource_Array
{
    function Structures_DataGrid_DataSource_Excel()
    {
        parent::Structures_DataGrid_DataSource_Array();
        $this->_addDefaultOptions(array('header'    => false));
    }

    /**
     * Bind
     *
     * @param   mixed $filename   Path/filename to the Excel file
     * @access  public
     * @return  mixed             True on success, PEAR_Error on failure
     */    
    function bind($filename, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        $reader =& new Spreadsheet_Excel_Reader();
        $result = $reader->read($filename);
        if (PEAR::isError($result)) {
            return $result;
        }

        // if the options say that there is a header row, use the contents of it
        // as the column names
        if ($this->_options['header']) {
            $keys = array_values($reader->sheets[0]['cells'][1]);
            $startRow = 2;
        } else {
            $keys = null;
            $startRow = 1;
        }

        // store every field (column name) that is actually used
        $fields = $keys;

        // helper variable for the case that we have a file without a header row
        $maxkeys = 0;

        for ($i = $startRow; $i <= $reader->sheets[0]['numRows']; $i++) {
            if (empty($keys)) {
                $this->_ar[] = $reader->sheets[0]['cells'][$i];
                $maxkeys = max($maxkeys, $reader->sheets[0]['numCols']);
            } else {
                $rowAssoc = array();
                for ($j = 1; $j <= $reader->sheets[0]['numCols']; $j++) {
                    if (!empty($keys[$j - 1])) {
                        $key = $keys[$j - 1];
                    } else {
                        // there are more fields than we have column names
                        // from the header of the CSV file => we need to use
                        // the numeric index.
                        if (!in_array($j - 1, $fields, true)) {
                            $fields[] = $j - 1;
                        }
                        $key = $j - 1;
                    }
                    if (isset($reader->sheets[0]['cells'][$i][$j])) {
                        $rowAssoc[$key] = $reader->sheets[0]['cells'][$i][$j];
                    }
                }
                $this->_ar[] = $rowAssoc;
            }
        }

        // set field names if they were not set as an option
        if (!$this->_options['fields']) {
            if (empty($keys)) {
                $this->_options['fields'] = range(0, $maxkeys);
            } else {
                $this->_options['fields'] = $fields;
            }
        }

        return true;
    }
}

?>
