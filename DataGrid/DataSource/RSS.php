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

require_once 'Structures/DataGrid/DataSource/Array.php';
require_once 'XML/RSS.php';

/**
 * RSS data source driver
 *
 * @author      Andrew Nagy <asnagy@webitecture.org>
 * @package     Structures_DataGrid
 * @category    Structures
 * @version     $Revision $
 */
class Structures_DataGrid_DataSource_RSS extends Structures_DataGrid_DataSource
{
    var $_ar = array();

    /**
     * Constructor
     * 
     */
    function Structures_DataGrid_DataSource_RSS()
    {
        parent::Structures_DataGrid_DataSource();
    }

    /**
     * Bind RSS data 
     * 
     * @access  public
     * @param   string $file        RSS file
     * @param   array $options      Options as an associative array
     * @return  void on success, PEAR_Error on failure 
     */
    function bind($file, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        $rss = new XML_RSS($file);
        $this->_ar = $rss->getItems();
        
        return true;
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
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @access  public
     * @return  array       The 2D Array of the records
     */
    function &fetch($offset=0, $len=null, $sortField='', $sortDir='ASC')
    {
        $records =& Structures_DataGrid_DataSource_Array::staticFetch(
                        $this->_ar, $this->_options['fields'], $offset, 
                        $len, $sortField, $sortDir);
        return $records;
    }
}

?>