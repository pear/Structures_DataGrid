<?php
/**
 * PEAR::DB_Table DataSource Driver
 * 
 * <pre>
 * +----------------------------------------------------------------------+
 * | PHP version 4                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2005 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | This source file is subject to version 2.0 of the PHP license,       |
 * | that is bundled with this package in the file LICENSE, and is        |
 * | available through the world-wide-web at                              |
 * | http://www.php.net/license/2_02.txt.                                 |
 * | If you did not receive a copy of the PHP license and are unable to   |
 * | obtain it through the world-wide-web, please send a note to          |
 * | license@php.net so we can mail you a copy immediately.               |
 * +----------------------------------------------------------------------+
 * | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
 * |          Mark Wiesemann <wiesemann@php.net>                          |
 * +----------------------------------------------------------------------+
 * </pre>
 *
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_DataSource_DBTable
 * @category Structures
 */

require_once 'Structures/DataGrid/DataSource.php';

/**
 * PEAR::DB_Table Data Source Driver
 *
 * This class is a data source driver for the PEAR::DB_Table object
 *
 * SUPPORTED OPTIONS:
 * 
 * - view:   (string)  The view from $sql array in your DB_Table object. This
 *                     option is required.
 * - where:  (string)  A where clause for the SQL query.
 *                     (default: null)
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_DataSource_DBTable
 * @category Structures
 */
class Structures_DataGrid_DataSource_DBTable
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
     * Fields/directions to sort the data by
     *
     * @var array Structure: array(fieldName => direction, ....)
     * @access private
     */
    var $_sortSpec = array();
    
    /**
     * Constructor
     *
     * @access public
     */
    function Structures_DataGrid_DataSource_DBTable()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array('where' => null));

        // FIXME: For clarity, supported options should be declared with 
        // _addDefaultOptions()

        $this->_setFeatures(array('multiSort' => true));
    }
  
    /**
     * Bind
     *
     * @param   object DB_Table     $object     The object (subclass of
     *                                          DB_Table) to bind
     * @param   mixed               $options    array('view' => 
     *                                          [name of "view" key])
     * @access  public
     * @return  mixed               True on success, PEAR_Error on failure
     */
    function bind(&$object, $options=array())
    {
        if (is_object($object) && is_subclass_of($object, 'db_table')) {
            $this->_object =& $object;
        } else {
            return PEAR::raiseError(
                'The provided source must be a subclass of DB_Table');
        }

        if (array_key_exists('view', $options) &&
            array_key_exists($options['view'], $object->sql)) {
            $this->setOptions($options);
            return true;
        } else {
            return PEAR::raiseError('Invalid "view" specified ' . 
                '[must be a key in array of DB_Table subclass]');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Offset (starting from 0)
     * @param   integer $limit      Limit
     * @access  public
     * @return  array               The 2D Array of the records
     */
    function &fetch($offset=0, $limit=null)
    {
        if (!empty($this->_sortSpec)) {
            foreach ($this->_sortSpec as $field => $direction) {
                $sortArray[] = "$field $direction";
            }
            $sortString = join(', ', $sortArray);
        } else {
            $sortString = null;
        }

        $this->_result = $this->_object->selectResult(
                            $this->_options['view'],
                            $this->_options['where'], 
                            $sortString, 
                            $offset, $limit);

        if (PEAR::isError($this->_result)) {
            return $this->_result;
        }

        if (is_a($this->_result, 'db_result')) {
            $fetchmode = DB_FETCHMODE_ASSOC;
        } else {
            $fetchmode = MDB2_FETCHMODE_ASSOC;
        }

        $recordSet = array();

        // Fetch the Data
        if ($numRows = $this->_result->numRows()) {
            while ($record = $this->_result->fetchRow($fetchmode)) {
                $recordSet[] = $record;
            }
        }

        // Determine fields to render
        if (!$this->_options['fields'] && count($recordSet)) {
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
        return $this->_object->selectCount($this->_options['view'],
                                           $this->_options['where']);
    }

    /**
     * This can only be called prior to the fetch method.
     *
     * @access  public
     * @param   mixed   $sortSpec   A single field (string) to sort by, or a 
     *                              sort specification array of the form:
     *                              array(field => direction, ...)
     * @param   string  $sortDir    Sort direction: 'ASC' or 'DESC'
     *                              This is ignored if $sortDesc is an array
     */
    function sort($sortSpec, $sortDir = 'ASC')
    {
        if (is_array($sortSpec)) {
            $this->_sortSpec = $sortSpec;
        } else {
            $this->_sortSpec[$sortSpec] = $sortDir;
        }
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
