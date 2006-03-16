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
 * Structures_DataGrid_Renderer_CSV Class
 *
 * Recognized options:
 *
 * - delimiter: (string) The delimiter to use to seperate the values
 *                       (default: ',')
 * - lineBreak: (string) The character(s) to use for line breaks
 *                       (default: '\n')
 * - useQuotes: (bool)   Whether or not to encapuslate the values with quotes
 *                       (default: false)
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
                'lineBreak' => "\n",
                'useQuotes' => false
            )
        );
    }

    /**
     * Initialize a string for the CSV if it is not already existing
     * 
     * @access protected
     */
    function init()
    {
        if (is_null($this->_container)) {
            $this->_container = '';
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
    function buildHeader()
    {
        $csv = '';

        for ($col = 0; $col < $this->_columnsNum; $col++) {
            if ($col > 0) {
                $csv .= $this->_options['delimiter'];
            }
            $csv .= $this->_columns[$col]['label'];
        }

        $csv .= $this->_options['lineBreak'];

        $this->_container .= $csv;
    }

    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        $csv = '';

        for ($row = 0; $row < $this->_recordsNum; $row++) {
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                if ($col > 0) {
                    $csv .= $this->_options['delimiter'];
                }
                
                // Add content to CSV
                $content = $this->_records[$row][$col];
                if ($this->_options['useQuotes']) {
                    $content = '"' . str_replace('"', '""', $content) . '"';
                } else {
                    if (strstr($content, '"')) {
                        $content = '"' . str_replace('"', '""', $content) . '"';
                    } elseif (strstr($content, ',')) {
                        $content = '"' . $content . '"';
                    }
                }

                $csv .= $content;
            }

            $csv .= $this->_options['lineBreak'];
        }

        $this->_container .= $csv;
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
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        return $this->_container;
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
}

?>
