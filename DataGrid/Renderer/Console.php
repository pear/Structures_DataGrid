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
require_once 'Console/Table.php';

/**
 * Structures_DataGrid_Renderer_Console Class
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_Console extends Structures_DataGrid_Renderer_Common
{
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @access  public
     */
    function Structures_DataGrid_Renderer_Console()
    {
        parent::Structures_DataGrid_Renderer_Common();
    }

    /**
     * Initialize Console_Table instance if it is not already existing
     * 
     * @access protected
     */
    function init()
    {
        if (is_null($this->_container)) {
            $this->_container = new Console_Table();
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
    function getTable()
    {
        return $this->_container;
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
        
        $this->_container->setHeaders($columnList);
    }

    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        // FIXME: don't start in row 0; instead start after the last existing
        // row in the table (requires a patch [new method getRowCount()] for
        // Console_Table and a new release of this package, of course)
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $cellList = array();
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $cellList[] = $this->_records[$row][$col];
            }
            $this->_container->addRow($cellList);
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
        return $this->_container->getTable();
    }

}

?>
