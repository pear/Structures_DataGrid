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

require_once 'Structures/DataGrid/Renderer/Common.php';
require_once 'Spreadsheet/Excel/Writer.php';

/**
 * Structures_DataGrid_Renderer_XLS Class
 *
 * Recognized options:
 *
 * - headerFormat:  (mixed)  The format for header cells (either 0 or
 *                           Spreadsheet_Excel_Writer_Format object)
 *                           (default: 0 [= "no format"])
 * - bodyFormat:    (mixed)  The format for body cells (either 0 or
 *                           Spreadsheet_Excel_Writer_Format object)
 *                           (default: 0 [= "no format"])
 * - filename:      (string) The filename of the spreadsheet
 *                           (default: 'spreadsheet.xls')
 * - sendToBrowser: (bool)   Should the spreadsheet be send to the browser?
 *                           (true = send to browser, false = write to a file)
 *                           (default: true)
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_XLS extends Structures_DataGrid_Renderer_Common
{
// FIXME: refactoring incomplete
// FIXME: remove $_workbook, use $_container (?)
// FIXME: test me
    
    /**
     * The spreadsheet object
     * @var object Spreadsheet_Excel_Writer
     */
    var $_workbook;
    
    /**
     * The worksheet object
     * @var object Spreadsheet_Excel_Writer
     */
    var $_worksheet;    

    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XLS()
    {
        parent::Structures_DataGrid_Renderer_Common();
        $this->_addDefaultOptions(
            array(
                'headerFormat'  => 0,
                'bodyFormat'    => 0,
                'filename'      => 'spreadsheet.xls',
                'sendToBrowser' => true
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
            // FIXME: create Spreadsheet_Excel_Writer instance here
        }
    }

    /**
     * Sets the name of the file to create
     *
     * This is incompatible with the setCustomWriter() method
     * 
     * @param  string   $filename        The name of the file
     * @param  bool     $sendToBrowser   Whether the spreadsheet should
     *                                   be send to the browser or written
     *                                   to a file
     * @access public
     * @see Structures_DataGrid_Renderer_XLS::setCustomWriter()
     */
    function setFilename($filename = 'spreadsheet.xls', $sendToBrowser = true)
    {
        $this->_options['filename'] = $filename;
        $this->_options['sendToBrowser'] = $sendToBrowser;
    }

    /**
     * Replace the internal Excel Writer with a custom one
     *
     * This is useful in order to customize your new XLS document
     * before Structures_DataGrid fills it with data
     * 
     * This method is incompatible with setFilename() 
     * 
     * @param object $workbook  Spreadsheet_Excel_Writer_Workbook object
     * @param object $worksheet Spreadsheet_Excel_Writer_Worksheet object
     * @see Structures_DataGrid_Renderer_XLS::setFilename()
     */
    function setCustomWriter(&$workbook, &$worksheet)
    {
        $this->_workbook =& $workbook;
        $this->_worksheet =& $worksheet;
    }

    /**
     * Set headers format
     * 
     * It is required to use setCustomWriter() before calling this method. 
     * The Format object provided to setHeaderFormat() has to be derived 
     * from the Workbook passed to setCustomWriter().
     *
     * @param object $format Spreadsheet_Excel_Writer_Format object
     * @see Structures_DataGrid_Renderer_XLS::setCustomWriter()
     */
    function setHeaderFormat(&$format)
    {
        $this->_options['headerFormat'] =& $format;
    }

    /**
     * Set body format
     *
     * It is required to use setCustomWriter() before calling this method. 
     * The Format object provided to setBodyFormat() has to be derived 
     * from the Workbook passed to setCustomWriter().
     *
     * @param object $format Spreadsheet_Excel_Writer_Format object
     * @see Structures_DataGrid_Renderer_XLS::setCustomWriter()
     */
    function setBodyFormat(&$format)
    {
        $this->_options['bodyFormat'] =& $format;
    }

    /**
     * Get the spreadsheet object
     *
     * @access  public
     */
    function &getSpreadsheet()
    {
        // FIXME: parts of this needs to go into init(), other parts
        //        into flatten()
        //        Attention: setting the filename in the constructor call
        //                   is important when the spreadsheet should be
        //                   written to a file
        if (!isset($this->_workbook)) {
            if ($this->_sendToBrowser) {
                $this->_workbook = new Spreadsheet_Excel_Writer();
                $this->_workbook->send($this->_filename);
            } else {
                $this->_workbook = new Spreadsheet_Excel_Writer($this->_filename);
            }
        }

        if (!isset($this->_worksheet)) {
            $this->_worksheet =& $this->_workbook->addWorksheet();        
        }

        return $this->_workbook;
    }
    
    /**
     * Handles building the header of the table
     *
     * @access  protected
     * @return  void
     */
    function buildHeader()
    {
        for ($col = 0; $col < $this->_columnsNum; $col++) {
            $label = $this->_columns[$col]['label'];
            $this->_worksheet->write(0, $col, $label,
                                     $this->_options['headerFormat']);
        }
    }

    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        $startRow = $this->_options['buildHeader'] ? 1 : 0;
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $recordRow = $row + $startRow;
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $value = $this->_records[$row][$col];

                $this->_worksheet->write($recordRow, $col, $value,
                                         $this->_options['bodyFormat']);
            }
        }
    }

    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        $this->_workbook->close();
        return $this->_workbook;  // FIXME: is this right for both cases of
                                  // 'sendToBrowser'?
    }

}

?>
