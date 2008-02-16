<?php
/**
 * Array Data Source Driver
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
 * @package  Structures_DataGrid_DataSource_Array
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/DataSource.php';

/**
 * Array Data Source Driver
 *
 * This class is a data source driver for a 2D array
 *
 * SUPPORTED OPTIONS:
 * 
 * - natsort:  (boolean)  Whether the array should be sorted naturally (e.g.
 *                        example1, Example2, test1, Test2) or not (e.g.
 *                        Example2, Test2, example1, test1; i.e. capital
 *                        letters will come first).
 * 
 * GENERAL NOTES:
 *
 * This driver expects an array of the following form:
 * <code>
 * $data = array(0 => array('col0' => 'val00', 'col1' => 'val01', ...),
 *               1 => array('col0' => 'val10', 'col1' => 'val11', ...),
 *               ...
 *              );
 * </code>
 *
 * The first level of this array contains one entry for each row. For every
 * row entry an array with the data for this row is expected. Such an array
 * contains the field names as the keys. For example, 'val01' is the value
 * of the column with the field name 'col1' in the first row. Row numbers
 * start with 0, not with 1.
 *
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_DataSource_Array
 * @category Structures
 */
class Structures_DataGrid_DataSource_Array
    extends Structures_DataGrid_DataSource
{
    /**
     * The array
     *
     * @var array
     * @access private
     */
    var $_ar = array();
     
    function Structures_DataGrid_DataSource_Array()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array('natsort' => false));
    }

    /**
     * Bind
     *
     * @param   array $ar       The result object to bind
     * @access  public
     * @return  mixed           True on success, PEAR_Error on failure
     */    
    function bind($ar, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        } 
               
        if (is_array($ar)) {
            // if the array keys are non-continuous, reset the array keys to
            // ensure correct sorting
            $keys = array_keys($ar);
            if (count($keys) > 0 && count($ar) != max($keys) + 1) {
                $this->_ar = array_values($ar);
            } else {
                $this->_ar = $ar;
            }
            return true;
        } else {
            return PEAR::raiseError('The provided source must be an array');
        }
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number or records
     */
    function count()
    {
        return count($this->_ar);
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @access  public
     * @return  array               Array of records
     */
    function &fetch($offset = 0, $len = null)
    {
        if ($this->_ar && !$this->_options['fields']) {
            $firstElement = array_slice($this->_ar, 0, 1);
            $this->setOptions(array('fields' => array_keys((array) $firstElement[0])));
        }

        // slicing
        if (is_null($len)) {
            $slice = array_slice($this->_ar, $offset);
        } else {
            $slice = array_slice($this->_ar, $offset, $len);
        }

        // Filter out fields that are to not be rendered (object records 
        // excepted)
        $records = array();
        if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
            foreach ($slice as $key => $rec) {
                if (is_array($rec)) {
                    $records[] = array_intersect_key($rec, array_flip($this->_options['fields']));
                } else {
                    $records[] =& $slice[$key];
                }
            }
        } else {
            foreach ($slice as $key => $rec) {
                if (is_array($rec)) { 
                    $buf = array();
                    foreach ($rec as $key => $val) {
                        if (in_array($key, $this->_options['fields'])) {
                            $buf[$key] = $val;
                        }
                    }
                    $records[] = $buf;
                } else {
                    $records[] =& $slice[$key];
                }
            }
        }

        return $records;
    }

    /**
     * Sorts the array.
     * 
     * @access  public
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction: 'ASC' or 'DESC' 
     *                              (default: ASC)
     */
    function sort($sortField, $sortDir = null)
    {
        $sortAr = array();
        $numRows = count($this->_ar);
        
        for ($i = 0; $i < $numRows; $i++) {
            $rec = (array) $this->_ar[$i];
            $sortAr[$i] = $rec[$sortField];
        }

        $sortDir = (is_null($sortDir) or strtoupper($sortDir) == 'ASC') 
                 ? SORT_ASC : SORT_DESC;
        if ($this->_options['natsort']) {
            $sortAr = array_map('strtolower', $sortAr);
        }
        array_multisort($sortAr, $sortDir, $this->_ar);
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
