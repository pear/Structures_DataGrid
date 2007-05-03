<?php
/**
 * Comma Seperated Value (CSV) Data Source Driver
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Andrew Nagy <asnagy@webitecture.org>,
 *                          Olivier Guilyardi <olivier@samalyse.com>,
 *                          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_DataSource_CSV
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
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
 * - header:     (bool)    Whether the CSV file (or string) contains a header row
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
            $keys = $this->_parseRow($rowList[0]);
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
                    $rowArray = $this->_parseRow($row);
                    $this->_ar[] = $rowArray;
                    $maxkeys = max($maxkeys, count($rowArray));
                } else {
                    $rowAssoc = array();
                    $rowArray = $this->_parseRow($row);
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

    /**
     * Parse a row that contains CSV data
     *
     * @param   string   $row   A string that contains CSV data
     * @access  public
     * @return  array           The splitted CSV data as an array
     */    
    function _parseRow($row)
    {
        $delimiter = $this->_options['delimiter'];
        if (in_array($delim, array(',', '|'))) {
            $delimiter = '\\' . $delimiter;
        }
        $regexp = "/{$delimiter}(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/";
        $results = preg_split($regexp, $row);
        return preg_replace('/^"(.*)"$/', '$1', $results);
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
