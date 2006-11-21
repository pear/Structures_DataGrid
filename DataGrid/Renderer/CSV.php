<?php
/**
 * CSV Rendering Driver
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
 * @package  Structures_DataGrid_Renderer_CSV
 * @category Structures
 */

require_once 'Structures/DataGrid/Renderer.php';

/**
 * CSV Rendering Driver
 *
 * SUPPORTED OPTIONS:
 *
 * - delimiter:  (string)  Field delimiter
 *                         (default: ',')
 * - filename:   (string)  Filename of the generated CSV file; boolean false
 *                         means that no filename will be sent
 *                         (default: false)
 * - saveToFile: (boolean) Whether the output should be saved on the local
 *                         filesystem. Please note that the 'filename' option
 *                         must be given if this optio is set to true.
 *                         (default: false)
 * - enclosure:  (string)  Field enclosure
 *                         (default: a double quotation mark: ")
 * - lineBreak:  (string)  The character(s) to use for line breaks
 *                         (default: '\n')
 * - useQuotes:  (mixed)   Whether or not to encapsulate the values with the 
 *                         enclosure value.
 *                         true: always, false: never, "auto": when needed
 *                         (default: "auto")
 *                       
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: no
 * - Output Buffering:  yes
 * - Direct Rendering:  no
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
                'enclosure'  => '"',
                'lineBreak'  => "\n",
                'useQuotes'  => "auto"
            )
        );
        $this->_setFeatures(array('streaming' => true));
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
            // TODO: check filename (must not be boolean false)
            // TODO: filename should be writable or fopen() should be checked for errors
            $this->_fp = fopen($this->_options['filename'], 'wb');
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
            fwrite($this->_fp, $csv);
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
            fwrite($this->_fp, $csv);
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
            fclose($this->_fp);
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
