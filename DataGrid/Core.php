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

require_once 'DataGrid/Column.php';
require_once 'DataGrid/Record.php';

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
     * @access public
     * @param  object $column    The column object to add. This object should be
     *                          a HTML_DataGridColumn object.
     * @return bool           True if successful, otherwise false.
     */
    function addColumn($column)
    {
        if (get_class($column) == 'structures_datagrid_column') {
            $this->columnSet = array_merge($this->columnSet, array($column));
            return true;
        } else {
            return false;
        }
    }

    /**
     * A simple way to add an associative array record set to the data grid.
     *
     * @access public
     * @param  array $rs        The associative array recordset
     * @return bool             True if successful, otherwise false.
     */
    function bind($rs)
    {
        if (is_array($rs)) {
            $this->recordSet = $rs;
            return true;
        } else {
            return new PEAR_Error('Recordset must be an associative array');
        }
    }

    /**
     * Adds a DataGrid_Record object to this DataGrid object
     *
     * @access public
     * @param  object $record   The record object to add. This object must be
     *                          a HTML_DataGridRecord object.
     * @return bool             True if successful, otherwise false.
     */
    function addRecord($record)
    {
        if ((get_class($record) == 'structures_datagrid_record') ||
            (get_parent_class($record) == 'structures_datagrid_record')) {
            $this->recordSet = array_merge($this->recordSet,
                                           array($record->getRecord()));
            return true;
        } else {
            return new PEAR_Error('Not a valid DataGrid Record');
        }
    }

    /**
     * Drops a DataGridRecord object from this DataGrid object
     *
     * @access public
     * @param  object DataGrid_Record    $record      The record object to drop.
     *                                                This object must be a
     *                                                Structures_DataGridRecord
     *                                                object.
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
        // Define sorting values
        $this->sortArray = array($sortBy, $direction);

        // Sort the recordset
        usort($this->recordSet, array($this, '_sort'));
    }

    function _sort($a, $b, $i = 0)
    {
        $bool = strnatcmp($a[$this->sortArray[0]], $b[$this->sortArray[0]]);

        if ($this->sortArray[1] == 'DESC') {
            $bool = $bool * -1;
        }

        return $bool;
    }

    /**
     * Handles building the paging of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function buildPaging()
    {
        if (($this->rowLimit > 0) &&
            (count($this->recordSet) > $this->rowLimit)) {
            $pageCount = ceil(count(($this->recordSet))/($this->rowLimit));
            for ($i = 1; $i <= $pageCount; $i++) {
                if ($this->page == $i) {
                    $this->pageList[$i] = null;
                } else {
                    $this->pageList[$i] = "page=$i";
                }
            }
        }
    }

}

?>
