<?php
/**
 * Comma Seperated Value (CSV) Data Source Driver
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
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_DataSource_CSV
 * @category Structures
 */

require_once 'Structures/DataGrid/DataSource/Array.php';

/**
 * Comma Seperated Value (CSV) Data Source Driver
 *
 * This class is a data source driver for a CSV File.  It will also support any
 * other delimiter.
 *
 * SUPPORTED OPTIONS:
 *
 * - delimiter:  (string)  Field delimiter
 *                         (default: ',')
 * - header:     (bool)    Whether the CSV file (or string) contains a header row
 *                         (default: false)
 * 
 * @version  $Revision$
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_DataSource_CSV
 * @category Structures
 */
class Structures_DataGrid_DataSource_CSV extends
    Structures_DataGrid_DataSource_Array
{
    function Structures_DataGrid_DataSource_CSV()
    {
        parent::Structures_DataGrid_DataSource_Array();
        $this->_addDefaultOptions(array('delimiter' => ',',
                                        'header'    => false));
    }

    /**
     * Bind
     *
     * @param   mixed $csv      Can be either the path to the CSV file or a
     *                          string containing the CSV data
     * @access  public
     * @return  mixed           True on success, PEAR_Error on failure
     */    
    function bind($csv, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        if (strlen($csv) < 256 && @is_file($csv)) {
            // TODO: do not read the whole file at once
            if (!$rowList = file($csv)) {
                return PEAR::raiseError('Could not read file');
            }
        } else {
            $rowList = explode("\n", $csv);
        }

        // if the options say that there is a header row, use the contents of it
        // as the column names
        if ($this->_options['header']) {
            $keys = explode($this->_options['delimiter'], rtrim($rowList[0]));
            unset($rowList[0]);
        } else {
            $keys = null;
        }

        // store every field (column name) that is actually used
        $fields = $keys;

        // helper variable for the case that we have a file without a header row
        $maxkeys = 0;

        foreach ($rowList as $row) {
            $row = rtrim($row); // to remove DOSish \r
            if (!empty($row)) {
                if (empty($keys)) {
                    $rowArray = explode($this->_options['delimiter'], $row);
                    $this->_ar[] = $rowArray;
                    $maxkeys = max($maxkeys, count($rowArray));
                } else {
                    $rowAssoc = array();
                    $rowArray = explode($this->_options['delimiter'], $row);
                    foreach ($rowArray as $index => $val) {
                        if (!empty($keys[$index])) {
                            $rowAssoc[$keys[$index]] = $val;
                        } else {
                            // there are more fields than we have column names
                            // from the header of the CSV file => we need to use
                            // the numeric index.
                            if (!in_array($index, $fields, true)) {
                                $fields[] = $index;
                            }
                            $rowAssoc[$index] = $val;
                        }
                    }
                    $this->_ar[] = $rowAssoc;
                }
            }
        }
        
        // set field names if they were not set as an option
        if (!$this->_options['fields']) {
            if (empty($keys)) {
                $this->_options['fields'] = range(0, $maxkeys - 1);
            } else {
                $this->_options['fields'] = $fields;
            }
        }

        return true;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
