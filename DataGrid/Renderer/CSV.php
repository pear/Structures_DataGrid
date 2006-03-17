<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2005 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
// |          Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Mark Wiesemann <wiesemann@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'Structures/DataGrid/Renderer.php';

/**
 * CSV Rendering Driver
 *
 * Recognized options:
 *
 * - delimiter: (string) Field delimiter
 *                       (default: ',')
 * - enclosure: (string) Field enclosure
 *                       (default is a double quotation mark: ")
 * - lineBreak: (string) The character(s) to use for line breaks
 *                       (default: '\n')
 * - useQuotes: (mixed)  Whether or not to encapuslate the values with the 
 *                       enclosure value.
 *                       true: always, false: never, "auto": when needed
 *                       (default: "auto")
 *                       
 * GENERAL NOTES :
 *
 * This driver has no container support. You can not use 
 * Structures_DataGrid::fill() with it.
 *
 * It buffers output, you can use getOutput().
 *                       
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_CSV extends Structures_DataGrid_Renderer
{
    // FIXME: It would be very interesting to implement some sort of CSV 
    // streaming feature for large datasets. The datasource layer should
    // read data chunk by chunk and this driver (and possibly others) could
    // stream it directly to the browser.
    // What implications on the renderer driver interface ? Is it ready for
    // such streaming ? A new CSVStream driver ?
    
    /**
     * CSV output
     * @var string
     * @access private
     */
    var $_csv;
    
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
                'delimiter' => ',',
                'enclosure' => '"',
                'lineBreak' => "\n",
                'useQuotes' => "auto"
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
        $this->_csv .= $this->_recordToCsv($data);
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
        $this->_csv .= $this->_recordToCsv($data);
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
        header('Content-type: text/csv');
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
        // FIXME: what about concatenating directly into $this->_csv ?
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

        $str .= $this->_options['lineBreak'];

        return $str;
    }
}

?>
