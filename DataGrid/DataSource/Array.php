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
// | Author: Olivier Guilyardi <olivier@samalyse.com>                     |
// +----------------------------------------------------------------------+
//
// $Id $

require_once 'Structures/DataGrid/Source.php';

/**
 * Array Data Source Driver
 *
 * This class is a data source driver for a 2D Array
 *
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_Array extends Structures_DataGrid_DataSource
{
    /**
     * The array
     *
     * @var array
     * @access private
     */
    var $_ar;

    var $_offset = 0;
    var $_limit  = null;
     
    function Structures_DataGrid_DataSource_Array()
    {
        parent::Structures_DataGrid_DataSource();
    }

    /**
     * Bind
     *
     * @param   array $ar       The result object to bind
     * @access  public
     * @return  mixed           True on success, PEAR_Error on failure
     */    
    function bind($ar)
    {
        if (is_array($ar)) {
            $this->_ar =& $ar;
            return true;
        } else {
            return new PEAR_Error('The provided source must be an array');
        }
    }

    /**
     * Sort
     *
     * @access  public
     * @param   string $field       The field to sort by
     * @param   string $direction   The direction to sort, either ASC or DESC
     */    
    function sort($field, $direction='ASC')
    {
        $numRows = count($this->_ar);
        $sortAr = array();
        for ($i = 0; $i < $numRows; $i++) {
            $sortAr[$i] = $this->_ar[$i][$field];
        }
        $direction = strtoupper($direction) == 'ASC' ? SORT_ASC : SORT_DESC;
        array_multisort($sortAr, $direction, $this->_ar);
    }

    /**
     * Limit
     *
     * @access  public
     * @param   int $offset     The count offset
     * @param   int $length     The amount to limit to
     */     
    function limit($offset, $length)
    {
        $this->_offset = $offset;
        $this->_limit  = $length;
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number or records
     */
    function count()
    {
        return count($this->_ar);
    }

    /**
     * Fetch
     *
     * @access  public
     * @return  array       The 2D Array of the records
     */
    function &fetch()
    {
        if (is_null($this->_limit)) {
            $slice = array_slice($this->_ar, $this->_offset);
        } else {
            $slice = array_slice($this->_ar, $this->_offset, $this->_limit);
        }

        return array('Records' => $slice);
    }
}

?>