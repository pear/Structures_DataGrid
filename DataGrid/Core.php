<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
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

require_once 'Structures/DataGrid/Column.php';
require_once 'Structures/DataGrid/Record.php';

/**
 * Structures_DataGrid_Core Class
 *
 * The Core class implements the Core functionality of the DataGrid.
 * It offers the paging and sorting methods as well as the record and column
 * management methods.
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Core
{
    /**
     * Array of columns.  Columns are defined as a DataGridColumn object.
     * @var array
     */
    var $columnSet = array();

    /**
     * Array of records.  Records are defined as a DataGridRecord object.
     * @var array
     */
    var $recordSet = array();

    /**
     * The Data Source Driver object
     * @var object Structures_DataGrid_DataSource
     */
    var $_dataSource;    
    
    /**
     * An array of fields to sort by.  Each field is an array of the field name
     * and the direction, either ASC or DESC.
     * @var array
     */
    var $sortArray;

    /**
     * Limit of records to show per page.
     * @var string
     */
    var $rowlimit;

    /**
     * The current page to show.
     * @var string
     */
    var $page;

    /**
     * The array of available pages.
     * @var array
     */
    var $pageList = array();

    /**
     * Constructor
     *
     * Creates default table style settings
     *
     * @param  string   $limit  The row limit per page.
     * @param  string   $page   The current page viewed.
     * @access public
     */
    function Structures_DataGrid_Core($limit = null, $page = 1)
    {
        $this->rowLimit = $limit;
        $this->page = $page;
    }

    /**
     * Retrieves the current page number when paging is implemented
     *
     * @return string    the current page number
     * @access public
     */
    function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * Define the current page number.  This is used when paging is implemented
     *
     * @access public
     * @param  string    $page       The current page number.
     */
    function setCurrentPage($page)
    {
        $this->page = $page;
    }

    /**
     * Adds a DataGridColumn object to this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Column   $column     The column
     *          object to add. This object should be a
     *          Structures_DataGrid_Column object.
     * @return  bool    True if successful, otherwise false.
     */
    function addColumn($column)
    {
        if (is_a($column, 'structures_datagrid_column')) {
            $this->columnSet = array_merge($this->columnSet, array($column));
            return true;
        } else {
            return false;
        }
    }

    /**
     * A simple way to add a recod set to the datagrid
     *
     * @access  public
     * @param   mixed   $rs     The record set in any of the supported data
     *                          source types
     * @return  bool            True if successful, otherwise PEAR_Error.
     */
    function bind($rs)
    {
        require_once 'Structures/DataGrid/Source.php';
        
        $source =& Structures_DataGrid_DataSource::create($rs);
        if (!PEAR::isError($source)) {
            return $this->bindDataSource($source);
        } else {
            return new PEAR_Error('Recordset must be an associative array');
        }
    }

    /**
     * Allows binding to a data source driver.
     *
     * @access  public
     * @param   mixed   $source     The data source driver object
     * @return  mixed               True if successful, otherwise PEAR_Error
     */
    function bindDataSource(&$source)
    {
        if (is_subclass_of($source, 'structures_datagrid_datasource')) {
            $this->_dataSource =& $source;
            
            // ***************** FIXME *******************
            // The following code needs to be moved into the rendering stages
            $recordSet = $source->fetch(($this->page*$this->rowLimit),
                                   $this->rowLimit, $this->sortArray[0], 
                                   this->sortArray[1]);
            if (PEAR::isError($recordSet)) {
                return $recordSet;
            } else {
                $this->recordSet = $recordSet;
                if (count($columnSet = $source->getColumns())) {
                    $this->columnSet = $columnSet;
                }
            }
        } else {
            return new PEAR_Error('Invalid data source type, ' . 
                                  'must be a valid data source driver class');
        }
        
        return true;
    }
    
    /**
     * Adds a DataGrid_Record object to this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Record   $record     The record
     *          object to add. This object must be a Structures_DataGrid_Record
     *          object.
     * @return  bool            True if successful, otherwise false.
     */
    function addRecord($record)
    {
        if (is_a($record, 'structures_datagrid_record')) {
            $this->recordSet = array_merge($this->recordSet,
                                           array($record->getRecord()));
            return true;
        } else {
            return new PEAR_Error('Not a valid DataGrid Record');
        }
    }

    /**
     * Drops a DataGrid_Record object from this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Record   $record     The record
     *          object to drop. This object must be a Structures_DataGrid_Record
     *          object.
     * @return void
     */
    function dropRecord($record)
    {
        unset($this->recordSet[$record->getRecord()]);
    }

    /**
     * Sorts the records by the defined field.
     * Do not use this method if data is coming from a database as sorting
     * is much faster coming directly from the database itself.
     *
     * @access  public
     * @param   string $sortBy      The field to sort the record set by.
     * @param   string $direction   The sort direction, either ASC or DESC.
     * @return  void
     */
    function sortRecordSet($sortBy, $direction = 'ASC')
    {
        if ($this->_datasource) {
            $this->_datasource->sort($sortBy, $direction);
        }
        $this->sortArray = array($sortBy, $direction);
    }
}

?>
