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
// $Id $

require_once 'Structures/DataGrid/Source.php';

/**
 * PEAR::DB Source Driver
 *
 * This class is a data source driver for the PEAR::DB::DB_Result object
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Source_DB extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the DB_Result object
     *
     * @var object DB_Result
     * @access private
     */
    var $_result;

    /**
     * Constructor
     *
     * @access public
     */
    function Structures_DataGrid_DataSource_DB()
    {
        parent::Structures_DataGrid_DataSource();
    }
  
    /**
     * Bind
     *
     * @param   object DB_Result    The result object to bind
     * @access  public
     * @return  mixed               True on success, PEAR_Error on failure
     */
    function bind(&$result)
    {
        if (is_subclass_of($result, 'DB_Result')) {
            $this->_result =& $result;
            return true;
        } else {
            return new PEAR_Error('The provided source must be a DB_Result');
        }
    }

    function sort($sortArray)
    {
    }
    
    function limit($offset, $length)
    {
    }
    
    /**
     * Fetch
     *
     * @access  public
     * @return  array       The 2D Array of the records
     */
    function &fetch()
    {
        $recordSet = array();

        include_once 'Structures/DataGrid/Record/DB.php';

        if ($this->_result->numRows()) {
            while ($record = $this->_result->fetchRow(DB_FETCHMODE_ASSOC)) {
                $recordSet[] = new Structures_DataGrid_Record_DB($record);
            }
        } else {
            return new PEAR_Error('No records found');
        }
       
        return $recordSet;
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number or records
     */
    function count()
    {
        return $this->_result->numRows();
    }

}
?>