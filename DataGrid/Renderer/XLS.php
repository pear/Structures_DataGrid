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
// | Author: Andrew Nagy <asnagy@webitecture.org>                         |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'Spreadsheet/Excel/Writer.php';

/**
 * Structures_DataGrid_Renderer_XLS Class
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_XLS
{
    /**
     * The Datagrid object to render
     * @var object Structures_DataGrid
     */
    var $_dg;
    
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
     * The filename of the spreadsheet
     * @var string
     */
    var $_filename = 'spreadsheet.xls';

    /**
     * Whether the spreadsheet should be send to the browser
     * or written to a file
     * @var bool
     */
    var $_sendToBrowser = true;    
    
    /**
     * A switch to determine to use the header
     * @var bool
     */
    var $header = true;    
        
    /**
     * A switch to determine the state of the spreadsheet
     * @var bool
     */
    var $_rendered = false;    

    
    /**
     * Header format
     *
     * The 0 default value means "no format" as specified by the 
     * Spreadsheet_Excel_Writer_Worksheet::write() method prototype
     * 
     * @var mixed Spreadsheet_Excel_Writer_Format object or 0
     */
    var $_headerFormat = 0;

    /**
     * Body format
     *
     * The 0 default value means "no format" as specified by the 
     * Spreadsheet_Excel_Writer_Worksheet::write() method prototype
     * 
     * @var mixed Spreadsheet_Excel_Writer_Format object or 0
     */
    var $_bodyFormat = 0;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access public
     */
    function Structures_DataGrid_Renderer_XLS(&$dg)
    {
        $this->_dg =& $dg;
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
        $this->_filename = $filename;
        $this->_sendToBrowser = $sendToBrowser;
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
    function setCustomWriter (&$workbook, &$worksheet)
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
    function setHeaderFormat (&$format)
    {
        $this->_headerFormat =& $format;
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
    function setBodyFormat (&$format)
    {
        $this->_bodyFormat =& $format;
    }
    
    /**
     * Determines whether or not to use the header
     *
     * @access  public
     * @param   bool    $bool   value to determine to use the header or not.
     */
    function useHeader($bool)
    {
        $this->header = (bool)$bool;
    }    

    /**
     * Sets the rendered status.  This can be used to "flush the cache" in case
     * you need to render the datagrid twice with the second time having changes
     *
     * @access  public
     * @params  bool        $status     The rendered status of the DataGrid
     */
    function setRendered($status)
    {
        $this->_rendered = (bool)$status;
    }
        
    /**
     * Force download the spreadsheet
     *
     * @access  public
     */
    function render()
    {
        $this->getSpreadsheet();
       
        $this->_workbook->close();
    }

    /**
     * Get the spreadsheet object
     *
     * @access  public
     */
    function &getSpreadsheet()
    {
        $dg =& $this->_dg;
       
        if (!isset ($this->_workbook)) {
            if ($this->_sendToBrowser) {
                $this->_workbook = new Spreadsheet_Excel_Writer();
                $this->_workbook->send($this->_filename);
            } else {
                $this->_workbook = new Spreadsheet_Excel_Writer($this->_filename);
            }
        }

        if (!isset ($this->_worksheet)) {
            $this->_worksheet =& $this->_workbook->addWorksheet();        
        }
        
        if (!$this->_rendered) {        
            // Check to see if column headers exist, if not create them
            // This must follow after any fetch method call
            $dg->_setDefaultHeaders();
            
            if ($this->header) {
                $this->_buildHeader();
            }
            $this->_buildBody();
            $this->_rendered = true;
        }
        
        return $this->_workbook;
    }
    
    /**
     * Handles building the header of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function _buildHeader()
    {
        $cnt = 0;
        foreach ($this->_dg->columnSet as $column) {
            //Define Content
            $str = $column->columnName;
            $this->_worksheet->write(0, $cnt, $str, $this->_headerFormat);
            $cnt++;
        }
    }

    /**
     * Handles building the body of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function _buildBody()
    {
        if (count($this->_dg->recordSet)) {
            $rowCnt = $this->header ? 1 : 0;
            foreach ($this->_dg->recordSet as $row) {
                $cnt = 0;
                foreach ($this->_dg->columnSet as $column) {
                    // Build Content
                    if (!is_null ($column->formatter)) {
                        $content = $column->formatter($row);
                    } elseif (is_null ($column->fieldName)) {
                        if (!is_null ($column->autoFillValue)) {
                            $content = $column->autoFillValue;
                        } else {
                            $content = $column->columnName;
                        }
                    } else {
                        if (!isset ($row[$column->fieldName])) {
                            if (!is_null ($column->autoFillValue)) {
                                $content = $column->autoFillValue;
                            } else {
                                $content = '';
                            }
                        } else {
                            $content = $row[$column->fieldName];
                        }
                    }

                    $this->_worksheet->write($rowCnt, $cnt, $content, $this->_bodyFormat);

                    $cnt++;
                }
                $rowCnt++;
            }
        }
    }

}

?>
