<?php
/**
 * Console Table Rendering Driver
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
 * @package  Structures_DataGrid_Renderer_Console
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'Console/Table.php';

/**
 * Console Table Rendering Driver
 *
 * This renderer generates nicely formatted and padded ASCII tables.
 * 
 * SUPPORTED OPTIONS:
 *
 * - columnAttributes: (-) IGNORED
 * - jsHandler:        (-) IGNORED
 * - jsHandlerData:    (-) IGNORED
 * 
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: yes
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 * - Streaming:         no
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_Renderer_Console
 * @category Structures
 */
class Structures_DataGrid_Renderer_Console extends Structures_DataGrid_Renderer
{
    /**
     * Console_Table container
     * @var object
     * @access protected
     */
    var $_table;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @access  public
     */
    function Structures_DataGrid_Renderer_Console()
    {
        parent::Structures_DataGrid_Renderer();

        $this->_setFeatures(
            array(
                'outputBuffering' => true,
            )
        );
    }

    /**
     * Attach an already instantiated Console_Table object
     *
     * @param object $consoleTable 
     * @return mixed True or a PEAR_Error
     * @access public
     */
    function setContainer(&$consoleTable)
    {
        $this->_table =& $consoleTable;
        return true;
    }
    
    /**
     * Return the internal Console_Table container
     *
     * @return object Console_Table or PEAR_Error
     * @access public
     */
    function &getContainer(&$consoleTable)
    {
        if (!isset($this->_table)) {
            $id = __CLASS__ . '::' . __FUNCTION__;
            return PEAR::raiseError("$id: no Console_Table container loaded");
        }
        return $this->_table;
    }
    
    /**
     * Instantiate the Console_Table container if it hasn't been provided
     * 
     * @access protected
     */
    function init()
    {
        if (!isset($this->_table)) {
            $this->_table = new Console_Table();
        }
    }

    /**
     * Determines whether or not to use the Header
     *
     * @access  public
     * @param   bool    $bool   value to determine to use the header or not.
     */
    function useHeader($bool)
    {
        $this->_options['buildHeader'] = (bool)$bool;
    }

    /**
     * Returns the output of the table
     *
     * @access  public
     * @return  string      The output of the table
     */
    function toAscii()
    {
        return $this->getOutput();
    }
    
    /**
     * Gets the Console_Table object for the DataGrid
     *
     * OBSOLETE
     * 
     * @access  public
     * @return  object Console_Table   The Console_Table object for the DataGrid
     */
    function &getTable()
    {
        return $this->getContainer();
    }   
        
    /**
     * Handles building the header of the table
     *
     * @access  protected
     * @return  void
     */
    function buildHeader()
    {
        $columnList = array();
        for ($col = 0; $col < $this->_columnsNum; $col++) {
            $columnList[] = $this->_columns[$col]['label'];
        }
        
        $this->_table->setHeaders($columnList);
    }

    /**
     * Build a body row
     *
     * @param int   $index Row index (zero-based)
     * @param array $data  Record data. 
     *                     Structure: array(0 => <value0>, 1 => <value1>, ...)
     * @return void
     * @access protected
     * @abstract
     */
    function buildRow($index,$data)
    {
        $this->_table->addRow($data);
    }
    
    /**
     * Retrieve output from the container object 
     *
     * @return string Output
     * @access protected
     */
    function flatten()
    {
        return $this->_table->getTable();
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
