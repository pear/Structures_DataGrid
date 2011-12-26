<?php
/**
 * Comma Seperated Value (CSV) Data Source Driver
 * 
 * PHP version 5
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
 * - enclosure:  (string)  Field enclosure
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
                                        'enclosure' => '"',
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
            $fp = fopen($csv, 'rb');
            if (!$fp) {
                return PEAR::raiseError('Could not read file');
            }
            clearstatcache();
            $length = filesize($csv);
        } else {
            if (!Structures_DataGrid_DataSource_CSV_Memory::initialize(true)) {
                if (!stream_wrapper_register(
                        'structures-datagrid-datasource-csv-memory',
                        'Structures_DataGrid_DataSource_CSV_Memory')) {
                    return PEAR::raiseError('Could not register stream wrapper');
                }
                Structures_DataGrid_DataSource_CSV_Memory::initialize();
            }
            $fp = fopen('structures-datagrid-datasource-csv-memory://', 'r+');
            if (!$fp) {
                return PEAR::raiseError('Could not read from stream');
            }
            fwrite($fp, $csv);
            rewind($fp);
            $length = strlen($csv);
        }

        // if the options say that there is a header row, use the contents of it
        // as the column names
        if ($this->_options['header']) {
            $keys = fgetcsv($fp, $length, $this->_options['delimiter'],
                            $this->_options['enclosure']);
        } else {
            $keys = null;
        }

        // store every field (column name) that is actually used
        $fields = $keys;

        // helper variable for the case that we have a file without a header row
        $maxkeys = 0;

        while ($row = fgetcsv($fp, $length, $this->_options['delimiter'],
                              $this->_options['enclosure'])) {
            if (empty($keys)) {
                $this->_ar[] = $row;
                $maxkeys = max($maxkeys, count($row));
            } else {
                $rowAssoc = array();
                foreach ($row as $index => $val) {
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

/**
 * Stream wrapper for CSV DataSource driver
 *
 * This class is a stream wrapper for CSV data.
 *
 * @version  $Revision$
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_DataSource_CSV
 * @category Structures
 */
class Structures_DataGrid_DataSource_CSV_Memory
{
    /**
     * The current position in the stream
     *
     * @var integer
     * @access private
     */
    var $_position;

    /**
     * A string holding the stream data
     *
     * @var string
     * @access private
     */
    var $_varname;

    /**
     * This method is called immediately after the stream object is created.
     *
     * @param string    $path           Path (not used)
     * @param string    $mode           Mode (fopen(), not used)
     * @param integer   $options        Options (not used)
     * @param string    $opened_path    The opened path (not used)
     * @return boolean                  true on success, false on error
     */
    function stream_open($path, $mode, $options, $opened_path)
    {
        $this->_varname = '';
        $this->_position = 0;
        return true;
    }

    /**
     * This method is called in response to fread() and fgets() calls on the
     * stream. 
     *
     * @param integer   $count          The number of bytes that should be read
     * @return string                   The data that was read
     */
    function stream_read($count)
    {
        $ret = substr($this->_varname, $this->_position, $count);
        $this->_position += strlen($ret);
        return $ret;
    }

    /**
     * This method is called in response to fwrite() calls on the stream. 
     *
     * @param integer   $data           The data string that should be stored
     * @return string                   The number of bytes that was written
     */
    function stream_write($data)
    {
        $left = substr($this->_varname, 0, $this->_position);
        $right = substr($this->_varname, $this->_position + strlen($data));
        $this->_varname = $left . $data . $right;
        $this->_position += strlen($data);
        return strlen($data);
    }

    /**
     * This method is called in response to feof() calls on the stream.
     *
     * @return boolean                  Whether the read/write position is at
     *                                  the end of the stream 
     */
    function stream_eof()
    {
        return $this->_position >= strlen($this->_varname);
    }

    /**
     * This method is called in response to ftell() calls on the stream.
     *
     * @return integer                  The current read/write position of the
     *                                  stream
     */
    function stream_tell()
    {
        return $this->_position;
    }

    /**
     * This method is called in response to fseek() calls on the stream.
     *
     * @param integer $offset           Offset of the new position, added to the
     *                                  $whence position
     * @param integer $whence           Start position; one of: SEEK_SET,
     *                                  SEEK_CUR, SEEK_END
     * @return boolean                  true if position was changed, false if
     *                                  position could not be changed
     */
    function stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen($this->_varname) && $offset >= 0) {
                     $this->_position = $offset;
                     return true;
                } else {
                     return false;
                }
                break;
            case SEEK_CUR:
                if ($offset >= 0) {
                     $this->_position += $offset;
                     return true;
                } else {
                     return false;
                }
                break;
            case SEEK_END:
                if (strlen($this->_varname) + $offset >= 0) {
                     $this->_position = strlen($this->_varname) + $offset;
                     return true;
                } else {
                     return false;
                }
                break;
            default:
                return false;
        }
    }

    /**
     * PHP 4 workaround to indicate whether the class was already initialized
     * or not.
     *
     * @param boolean $checkOnly        Whether the status should only be
     *                                  checked.
     * @return mixed                    void or boolean
     */
    function initialize($checkOnly = false) {
        static $initialized = false;
        if ($checkOnly) {
            return $initialized;
        }
        $initialized = true;
    } 
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
