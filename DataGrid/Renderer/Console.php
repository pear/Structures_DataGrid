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
require_once 'Console/Table.php';

/**
 * Structures_DataGrid_Renderer_Console Class
 *
 * This container has container support. You can use the 
 * Structures_DataGrid::fill() method with it. 
 *
 * It buffers output, you can use Structures_DataGrid::getOutput()
 * 
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
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
     * Initialize Console_Table instance if it is not already existing
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
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $cellList = array();
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $cellList[] = $this->_records[$row][$col];
            }
            $this->_table->addRow($cellList);
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
        return $this->_table->getTable();
    }

}

?>
