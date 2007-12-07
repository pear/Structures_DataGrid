<?php
/**
 * CSV Rendering Driver
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
 * @package  Structures_DataGrid_Renderer_CSV
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/Renderer.php';

/**
 * CSV Rendering Driver
 *
 * SUPPORTED OPTIONS:
 *
 * - delimiter:  (string)  Field delimiter
 * - filename:   (string)  Filename of the generated CSV file; boolean false
 *                         means that no filename will be sent
 * - saveToFile: (boolean) Whether the output should be saved on the local
 *                         filesystem. Please note that the 'filename' option
 *                         must be given if this option is set to true.
 * - writeMode:  (string)  The mode that is used in the internal fopen() calls.
 *                         Useful e.g. when you want to append to existing file.
 *                         C.p. the fopen() documentation for the allowed modes.
 * - enclosure:  (string)  Field enclosure
 * - lineBreak:  (string)  The character(s) to use for line breaks
 * - useQuotes:  (mixed)   Whether or not to encapsulate the values with the 
 *                         enclosure value.
 *                         true: always, false: never, 'auto': when needed
 * - columnAttributes: (-) IGNORED
 * - onMove:           (-) IGNORED
 * - onMoveData:       (-) IGNORED
 *           
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: no
 * - Output Buffering:  yes
 * - Direct Rendering:  yes
 * - Streaming:         yes
 * - Object Preserving: no
 *                       
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_Renderer_CSV
 * @category Structures
 */
class Structures_DataGrid_Renderer_CSV extends Structures_DataGrid_Renderer
{
    /**
     * CSV output
     * @var string
     * @access private
     */
    var $_csv;
    
    /**
     * File pointer 
     * @var resource
     * @access private
     */
    var $_fp;

    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_CSV()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'delimiter'  => ',',
                'filename'   => false,
                'saveToFile' => false,
                'writeMode'  => 'wb',
                'enclosure'  => '"',
                'lineBreak'  => "\n",
                'useQuotes'  => "auto"
            )
        );
        $this->_setFeatures(
            array(
                'streaming' => true, 
                'outputBuffering' => true,
            )
        );
    }

    /**
     * Initialize CSV output
     * 
     * @access protected
     */
    function init()
    {
        $this->_csv = '';
        if ($this->_options['saveToFile'] === true) {
            if ($this->_options['filename'] === false) {
                return PEAR::raiseError('No filename specified via "filename" ' .
                                        'option.');
            }
            $this->_fp = fopen($this->_options['filename'],
                               $this->_options['writeMode']);
            if ($this->_fp === false) {
                return PEAR::raiseError('Could not open file "' .
                                        $this->_options['filename'] . '" ' .
                                        'for writing.');
            }
        }
    }

    /**
     * Define the CSV delimiter
     *
     * @access  public
     * @return  string      The CSV delimiter to use
     */
    function setDelimiter($delimiter)
    {
        $this->_options['delimiter'] = $delimiter;
    }

    /**
     * Define the character to use for line breaks
     *
     * @access  public
     * @return  string      The character(s) to use for line breaks (e.g. \n)
     */
    function setLineBreak($lineBreak)
    {
        $this->_options['lineBreak'] = $lineBreak;
    }    

    /**
     * Set the switch to encapsulate the values with quotes
     *
     * @access  public
     * @return  boolean     The boolean value to determine whether or not to
     *                      wrap values with quotes
     */
    function setUseQuotes($bool)
    {
        $this->_options['useQuotes'] = (bool)$bool;
    }

    /**
     * Handles building the header of the table
     *
     * @access  protected
     * @return  void
     */
    function buildHeader(&$columns)
    {
        $data = array();
        foreach ($columns as $spec) {
            $data[] = $spec['label'];
        }
        $csv = $this->_recordToCsv($data);
        if ($this->_options['saveToFile'] === true) {
            $res = fwrite($this->_fp, $csv);
            if ($res === false) {
                return PEAR::raiseError('Could not write into file "' .
                                        $this->_options['filename'] . '".');
            }
        } elseif ($this->_streamingEnabled) {
            echo $csv;
        } else {
            $this->_csv .= $csv;
        }
    }

    /**
     * Build a body row
     *
     * @param   int   $index Row index (zero-based)
     * @param   array $data  Record data. 
     * @access  protected
     * @return  void
     */
    function buildRow($index, $data)
    {
        $csv = $this->_recordToCsv($data);
        if ($this->_options['saveToFile'] === true) {
            $res = fwrite($this->_fp, $csv);
            if ($res === false) {
                return PEAR::raiseError('Could not write into file "' .
                                        $this->_options['filename'] . '".');
            }
        } elseif ($this->_streamingEnabled) {
            echo $csv;
        } else {
            $this->_csv .= $csv;
        }
    }

    /**
     * Returns the CSV format for the DataGrid
     *
     * @access  public
     * @return  string      The CSV of the DataGrid
     */
    function toCSV()
    {
        return $this->getOutput();
    }        

    /**
     * Finish building the datagrid.
     *
     * @access  protected
     * @return  void
     */
    function finalize()
    {
        if ($this->_options['saveToFile'] === true) {
            $res = fclose($this->_fp);
            if ($res === false) {
                return PEAR::raiseError('Could not close file "' .
                                        $this->_options['filename'] . '".');
            }
        }
    }

    /**
     * Retrieve output from the container object 
     *
     * @return string Output
     * @access protected
     */
    function flatten()
    {
        return $this->_csv;
    }

    /**
     * Render to the standard output
     *
     * @access  public
     */
    function render()
    {
        if ($this->_options['saveToFile'] === false) {
            header('Content-type: text/csv');
            if ($this->_options['filename'] !== false) {
                header('Content-disposition: attachment; filename=' .
                       $this->_options['filename']);
            }
        }
        parent::render();
    }

    /**
     * Convert record values into a CSV line
     *
     * @param  array  $data Record data
     * @return string CSV string, with a lineBreak included
     * @access protected
     */
    function _recordToCsv($data)
    {
        // This method is loosely inspired from PHP_Compat's fputcsv()
        $str = '';
        foreach ($data as $cell) {
            $cell = str_replace(
                        $this->_options['enclosure'], 
                        $this->_options['enclosure'].$this->_options['enclosure'],
                        $cell);

            if (($this->_options['useQuotes'] === true) 
                or ($this->_options['useQuotes'] == 'auto'
                    and (strchr($cell, $this->_options['delimiter']) !== false 
                         or strchr($cell, $this->_options['enclosure']) !== false 
                         or strchr($cell, $this->_options['lineBreak']) !== false))) {

                $str .= $this->_options['enclosure'] . 
                        $cell .
                        $this->_options['enclosure'] . 
                        $this->_options['delimiter'];
            } else {
                $str .= $cell . $this->_options['delimiter'];
            }
        }

        // remove the last delimiter because it would indicate an additional column
        $str = substr($str, 0, strlen($this->_options['delimiter']) * -1);

        $str .= $this->_options['lineBreak'];

        return $str;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
