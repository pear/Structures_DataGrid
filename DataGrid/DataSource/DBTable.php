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
// | Author: Andrew Nagy <asnagy@php.net>                                 |
// |         Mark Wiesemann <wiesemann@php.net>                           |
// +----------------------------------------------------------------------+
//
// $Id$

/**
* PEAR::DB_Table Data Source Driver
*
* This class is a data source driver for the PEAR::DB_Table object
*
* @version  $Revision$
* @author   Andrew S. Nagy <asnagy@php.net>
* @author   Mark Wiesemann <wiesemann@php.net>
* @access   public
* @package  Structures_DataGrid
* @category Structures
*/
class Structures_DataGrid_DataSource_DB_Table
    extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the Result object returned by DB_Table
     *
     * @var object DB_Result
     * @access private
     */
    var $_result;

    /**
     * Reference to the DB_Table object
     *
     * @var object DB_Table
     * @access private
     */
    var $_object;

    /**
     * The field to sort by
     *
     * @var string
     * @access private
     */
    var $_sortField;

    /**
     * The direction to sort by
     *
     * @var string
     * @access private
     */
    var $_sortDir;
    
    /**
     * Constructor
     *
     * @access public
     */
    function Structures_DataGrid_DataSource_DB_Table()
    {
        parent::Structures_DataGrid_DataSource();
    }
  
    /**
     * Bind
     *
     * @param   object DB_Table     The object (subclass of DB_Table) to bind
     * @param   mixed               Nothing or
     *                              array('view' => [name of "view" key])
     * @access  public
     * @return  mixed               True on success, PEAR_Error on failure
     */
    function bind(&$object, $options=array())
    {
        if (strtolower(get_parent_class($object)) == 'db_table') {
            $this->_object =& $object;
        } else {
            return new PEAR_Error(
                'The provided source must be a subclass of DB_Table');
        }

        if (array_key_exists('view', $options) &&
            array_key_exists($options['view'], $object->sql)) {
            $this->setOptions($options);
            return true;
        } else {
            return new PEAR_Error(
                'Invalid "view" specified ' . 
                '[must be a key in array of DB_Table subclass]');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Offset (starting from 0)
     * @param   integer $limit      Limit
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'    
     * @access  public
     * @return  array               The 2D Array of the records
     */
    function &fetch($offset=0, $limit=null, $sortField=null, $sortDir='ASC')
    {
        if (!is_null($sortField) && !is_null($sortDir)) {
            $this->_result = $this->_object->selectResult(
                                $this->_options['view'], null,
                                $sortField . ' ' . $sortDir, $offset, $limit);
        } elseif (!is_null($this->_sortField) && !is_null($this->_sortDir)) {
            $this->_result = $this->_object->selectResult(
                                $this->_options['view'], null, 
                                $this->_sortField . ' ' . $this->_sortDir, 
                                $offset, $limit);
        } else {
            $this->_result = $this->_object->selectResult(
                                $this->_options['view'], null, null, 
                                $offset, $limit);
        }

        $recordSet = array();

        // Fetch the Data
        if ($numRows = $this->_result->numRows()) {
            while ($record = $this->_result->fetchRow(DB_FETCHMODE_ASSOC)) {
                $recordSet[] = $record;
            }
        }

        // Determine fields to render
        if (!$this->_options['fields']) {
            $this->setOptions(array('fields' => array_keys($recordSet[0])));
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
        return $this->_object->selectCount($this->_options['view']);
    }
    
    /**
     * This can only be called prior to the fetch method.
     *
     * @access  public
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     */
    function sort($sortField, $sortDir)
    {
        $this->sortField = $sortField;
        $this->sortDir = $sortDir;
    }


}
?>