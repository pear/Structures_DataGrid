<?php
/**
 * Excel Spreadsheet Rendering Driver
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
 * @package  Structures_DataGrid_Renderer_XLS
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'Spreadsheet/Excel/Writer.php';

/**
 * Excel Spreadsheet Rendering Driver
 *
 * SUPPORTED OPTIONS:
 *
 * - headerFormat:  (mixed)  The format for header cells (either 0 [= "no format"]
 *                           or a Spreadsheet_Excel_Writer_Format object)
 *                           Please see the NOTE ABOUT FORMATTING below.
 * - bodyFormat:    (mixed)  The format for body cells (either 0 [= "no format"]
 *                           or a Spreadsheet_Excel_Writer_Format object)
 *                           Please see the NOTE ABOUT FORMATTING below.
 * - filename:      (string) The filename of the spreadsheet
 * - sendToBrowser: (bool)   Should the spreadsheet be send to the browser?
 *                           (true = send to browser, false = write to a file)
 * - worksheet:     (object) Optional reference to a
 *                           Spreadsheet_Excel_Writer_Worksheet object. You 
 *                           can leave this to null except if your workbook 
 *                           contains several worksheets and you want to fill
 *                           a specific one.
 * - startCol:      (int)    The Worksheet column number to start rendering at
 * - startRow:      (int)    The Worksheet row number to start rendering at
 * - border:        (int)    Border drawn around the whole datagrid: 
 *                           0 => none, 1 => thin, 2 => thick 
 *                           (NOT IMPLEMENTED YET)
 * - headerBorder:  (int)    Border between the header and body:
 *                           0 => none, 1 => thin, 2 => thick 
 *                           (NOT IMPLEMENTED YET)
 * - columnAttributes: (-)   IGNORED
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: yes
 * - Output Buffering:  no
 * - Direct Rendering:  not really, see below
 * - Streaming:         no
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
