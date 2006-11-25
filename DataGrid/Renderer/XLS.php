<?php
/**
 * Excel Spreadsheet Rendering Driver
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
 * @package  Structures_DataGrid_Renderer_XLS
 * @category Structures
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'Spreadsheet/Excel/Writer.php';

/**
 * Excel Spreadsheet Rendering Driver
 *
 * SUPPORTED OPTIONS:
 *
 * - headerFormat:  (mixed)  The format for header cells (either 0 or a
 *                           Spreadsheet_Excel_Writer_Format object)
 *                           Please see the NOTE ABOUT FORMATTING below.
 *                           (default: 0 [= "no format"])
 * - bodyFormat:    (mixed)  The format for body cells (either 0 or a
 *                           Spreadsheet_Excel_Writer_Format object)
 *                           Please see the NOTE ABOUT FORMATTING below.
 *                           (default: 0 [= "no format"])
 * - filename:      (string) The filename of the spreadsheet
 *                           (default: 'spreadsheet.xls')
 * - sendToBrowser: (bool)   Should the spreadsheet be send to the browser?
 *                           (true = send to browser, false = write to a file)
 *                           (default: true)
 * - worksheet:     (object) Optional reference to a
 *                           Spreadsheet_Excel_Writer_Worksheet object. You 
 *                           can leave this to null except if your workbook 
 *                           contains several worksheets and you want to fill
 *                           a specific one.
 *                           (default: null)
 * - startCol:      (int)    The Worksheet column number to start rendering at
 *                           (default: 0)
 * - startRow:      (int)    The Worksheet row number to start rendering at
 *                           (default: 0)
 * - border:        (int)    Border drawn around the whole datagrid: 
 *                           0 => none, 1 => thin, 2 => thick 
 *                           (default: 0) 
 *                           (NOT IMPLEMENTED YET)
 * - headerBorder:  (int)    Border between the header and body:
 *                           0 => none, 1 => thin, 2 => thick 
 *                           (default: 0)
 *                           (NOT IMPLEMENTED YET)
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: yes
 * - Output Buffering:  no
 * - Direct Rendering:  not really, see below
 * 
 * GENERAL NOTES:
 *
 * This driver does not support the flatten() method. You can not retrieve
 * its output with DataGrid::getOutput(). You can either render it directly 
 * to the browser or save it to a file. See the "sendToBrowser" and "filename" 
 * options.
 *
 * This driver has container support. You can use Structures_DataGrid::fill()
 * with it; that's even recommended.
 * 
 * NOTE ABOUT FORMATTING:
 * 
 * You can specify some formatting with the 'headerFormat' and 'bodyFormat' 
 * options, or with setBodyFormat() and setHeaderFormat(). 
 * 
 * But beware of the following from the Spreadsheet_Excel_Writer manual:
 * "Formats can't be created directly by a new call. You have to create a 
 * format using the addFormat() method from a Workbook, which associates your 
 * Format with this Workbook (you can't use the Format with another Workbook)."
 * 
 * What this means is that if you want to pass a format to this driver you
 * have to "derive" the Format object out of the workbook used in the driver.
 * 
 * The easiest way to do this is:
 *
 * <code>
 * // Create a workbook
 * $workbook = new Spreadsheet_Excel_Writer();
 *
 * // Specify that spreadsheet must be sent the browser
 * $workbook->send('test.xls');
 *
 * // Create your format
 * $format_bold =& $workbook->addFormat();
 * $format_bold->setBold();
 *
 * // Fill the workbook, passing the format as an option
 * $options = array('headerFormat' => &$format_bold);
 * $datagrid->fill($workbook, $options);
 * </code>
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_Renderer_XLS
 * @category Structures
 */
class Structures_DataGrid_Renderer_XLS extends Structures_DataGrid_Renderer
{
    /**
     * The spreadsheet container object
     * @var object Spreadsheet_Excel_Writer
     * @access private
     */
    var $_workbook;
    
    /**
     * The worksheet object
     * @var object Spreadsheet_Excel_Writer
     * @access private
     */
    var $_worksheet;    

    /**
     * The body row index to start rendering at
     * @var int
     * @access private
     */
    var $_bodyStartRow;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XLS()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'headerFormat'  => 0,
                'bodyFormat'    => 0,
                'filename'      => 'spreadsheet.xls',
                'sendToBrowser' => true,
                'worksheet'     => null,
                'startCol'      => 0,
                'startRow'      => 0,
                'border'        => 0,
                'headerBorder'  => 0
            )
        );
    }

    /**
     * Attach an already instantiated Spreadsheet_Excel_Writer object
     *
     * @param object $workbook Spreadsheet_Excel_Writer
     * @return mixed True or a PEAR_Error
     * @access public
     */
    function setContainer(&$workbook)
    {
        $this->_workbook =& $workbook;
        return true;
    }
   
    /**
     * Return a reference to the Spreadsheet_Excel_Writer object
     *
     * @return object Spreadsheet_Excel_Writer or PEAR_Error
     * @access public
     */
    function &getContainer()
    {
        isset($this->_workbook) or $this->init();
        return $this->_workbook;
    }
    
    /**
     * Instantiate the container if needed, and set it up
     * 
     * @access protected
     */
    function init()
    {
        if (!isset($this->_workbook)) {
            if ($this->_options['sendToBrowser']) {
                $this->_workbook = new Spreadsheet_Excel_Writer();
                $this->_workbook->send($this->_options['filename']);
            } else {
                $this->_workbook = new Spreadsheet_Excel_Writer($this->_options['filename']);
            }
            $this->_workbook->setVersion(8);
        }

        // Use user-provided worksheet if present
        if (!is_null($this->_options['worksheet'])) {
            $this->_worksheet =& $this->_options['worksheet'];
        } else {
            // Use the first worksheet or create one if the workbook is empty
            $worksheets = $this->_workbook->worksheets();
            if (empty($worksheets)) {
                $this->_worksheet =& $this->_workbook->addWorksheet();
                $this->_worksheet->setInputEncoding($this->_options['encoding']);
            } else {
                $this->_worksheet =& $worksheets[0];
            }
        }

        $this->_bodyStartRow  = $this->_options['startRow'];
        $this->_bodyStartRow += $this->_options['buildHeader'] ? 1 : 0;
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
     * It is recommended to use setContainer() or Structures_DataGrid::fill()
     * and the "worksheet" option instead of this method. 
     * 
     * This is useful in order to customize your new XLS document
     * before Structures_DataGrid fills it with data.
     * 
     * This method is incompatible with setFilename() 
     *
     * @param object $workbook  Spreadsheet_Excel_Writer_Workbook object
     * @param object $worksheet Spreadsheet_Excel_Writer_Worksheet object
     *                          (optional)
     * @see Structures_DataGrid_Renderer_XLS::setFilename()
     */
    function setCustomWriter(&$workbook, &$worksheet)
    {
        $this->setContainer($workbook);
        $this->_options['worksheet'] =& $worksheet;
    }

    /**
     * Set headers format
     * 
     * Please see the "NOTE ABOUT FORMATTING" in this class documentation
     *
     * @param object $format Spreadsheet_Excel_Writer_Format object
     * @see Structures_DataGrid_Renderer_XLS
     */
    function setHeaderFormat(&$format)
    {
        $this->_options['headerFormat'] =& $format;
    }

    /**
     * Set body format
     *
     * Please see the "NOTE ABOUT FORMATTING" in this class documentation
     * 
     * @param object $format Spreadsheet_Excel_Writer_Format object
     * @see Structures_DataGrid_Renderer_XLS
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
        return $this->getContainer();
    }
    
    /**
     * Handles building the header of the table
     *
     * @access  protected
     * @return  void
     */
    function buildHeader($columns)
    {
        foreach ($columns as $index => $spec) {
            $this->_worksheet->write($this->_options['startRow'], 
                                     $this->_options['startCol'] + $index, 
                                     $spec['label'],
                                     $this->_options['headerFormat']);
        }
    }

    /**
     * Build a body row
     *
     * @param int   $index Row index
     * @param array $data  Record data
     * @access  protected
     * @return  void
     */
    function buildRow($index, $data)
    {
        foreach ($data as $col => $value) {
            $this->_worksheet->write($this->_bodyStartRow + $index, 
                                     $this->_options['startCol'] + $col,
                                     $value, 
                                     $this->_options['bodyFormat']);
        }
    }

    /**
     * Output the datagrid or save it to a file 
     *
     * @return mixed True or PEAR_Error
     * @access protected
     */
    function render()
    {
        $this->isBuilt() or $this->build();
        $this->_workbook->close();
        return true;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
