<?php
/**
 * PEAR::DB_Table DataSource Driver
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
 * @package  Structures_DataGrid_DataSource_DBTable
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
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
 * - params: (array)   Placeholder parameters for prepare/execute
 * 
 * GENERAL NOTES:
 *
 * If you use aliases in the select part of your view, the count() method from
 * DB_Table and, therefore, $datagrid->getRecordCount() might return a wrong
 * result. To avoid this, DB_Table uses a special query for counting if it is
 * given via a view that needs to be named as '__count_' followed by the name
 * of the view that this counting view belongs to. (For example: if you have a
 * view named 'all', the counting view needs to be named as '__count_all'.)
 * 
 * To use update() and delete() methods, it is required that the indexes are
 * properly defined in the $idx array in your DB_Table subclass. If you have,
 * for example, created your database table yourself and did not setup the $idx
 * array, you can use the 'primaryKey' option to define the primary key field.
 *
 * @example  bind-dbtable.php  Bind a DB_Table class to Structures_DataGrid
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
     * Total number of rows 
     * 
     * This property caches the result of count() to avoid running the same
     * database query multiple times.
     *
     * @var int
     * @access private
     */
    var $_rowNum = null;    

   /**
     * Constructor
     *
     * @access public
     */
    function Structures_DataGrid_DataSource_DBTable()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array('view'   => null,
                                        'where'  => null,
                                        'params' => array()));
        $this->_setFeatures(array('multiSort' => true,
                                  'writeMode' => true));
    }
  
    /**
     * Bind
     *
     * @param   object DB_Table     $object     The object (subclass of
     *                                          DB_Table) to bind
     * @param   mixed               $options    Associative array of options.
     * @access  public
     * @return  mixed               True on success, PEAR_Error on failure
     */
    function bind(&$object, $options = array())
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
            return PEAR::raiseError('Invalid or no "view" specified ' . 
                '[must be a key in $sql array of DB_Table subclass]');
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
    function &fetch($offset = 0, $limit = null)
    {
        if (!empty($this->_sortSpec)) {
            foreach ($this->_sortSpec as $field => $direction) {
                $sortArray[] = $this->_object->db->quoteIdentifier($field) .
                               ' ' . $direction;
            }
            $sortString = join(', ', $sortArray);
        } else {
            $sortString = null;
        }

        if (is_subclass_of($this->_object->db, 'db_common')) {
            $this->_object->fetchmode = DB_FETCHMODE_ASSOC;
        } else {
            $this->_object->fetchmode = MDB2_FETCHMODE_ASSOC;
        }

        $recordSet = $this->_object->select(
                            $this->_options['view'],
                            $this->_options['where'], 
                            $sortString, 
                            $offset, $limit,
                            $this->_options['params']);
        if (PEAR::isError($recordSet)) {
            return $recordSet;
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
        // do we already have the cached number of records? (if yes, return it)
        if (!is_null($this->_rowNum)) {
            return $this->_rowNum;
        }
        // try to fetch the number of records
        $count = $this->_object->selectCount($this->_options['view'],
                                             $this->_options['where'],
                                             null, null, null,
                                             $this->_options['params']);
        // if we've got a number of records, save it to avoid running the same
        // query multiple times
        if (!PEAR::isError($count)) {
            $this->_rowNum = $count;
        }
        return $count;
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

    /**
     * Return the primary key field name or numerical index
     *
     * @return  mixed    on success: Field name(s) of primary/unique fields
     *                   on error: PEAR_Error with message 'No primary key found'
     * @access  protected
     */
    function getPrimaryKey()
    {
        if (!is_null($this->_options['primaryKey'])) {
            return $this->_options['primaryKey'];
        }
        include_once 'DB/Table/Manager.php';
        // try to find a primary key or unique index (for a single field)
        foreach ($this->_object->idx as $idxname => $val) {
            list($type, $cols) = DB_Table_Manager::_getIndexTypeAndColumns($val,
                                                                      $idxname);
            if ($type == 'primary' || $type == 'unique') {
                return (array)$cols;
            }
        }
        return PEAR::raiseError('No primary key found');
    }

    /**
     * Record insertion method
     *
     * @param   array   $data   Associative array of the form: 
     *                          array(field => value, ...)
     * @return  mixed           Boolean true on success, PEAR_Error otherwise
     * @access  public                          
     */
    function insert($data)
    {
        $result = $this->_object->insert($data);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Record updating method
     *
     * @param   string  $key    Unique record identifier
     * @param   array   $data   Associative array of the form: 
     *                          array(field => value, ...)
     * @return  mixed           Boolean true on success, PEAR_Error otherwise
     * @access  public                          
     */
    function update($key, $data)
    {
        $primary_key = $this->getPrimaryKey();
        if (PEAR::isError($primary_key)) {
            return $primary_key;
        }
        $where = array();
        foreach ($primary_key as $single_key) {
            $where[] = $single_key . '=' . $this->_object->quote($key[$single_key]);
        }
        $where_str = join(' AND ', $where);
        $result = $this->_object->update($data, $where_str);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Record deletion method
     *
     * @param   string  $key    Unique record identifier
     * @return  mixed           Boolean true on success, PEAR_Error otherwise
     * @access  public                          
     */
    function delete($key)
    {
        $primary_key = $this->getPrimaryKey();
        if (PEAR::isError($primary_key)) {
            return $primary_key;
        }
        $where = array();
        foreach ($primary_key as $single_key) {
            $where[] = $single_key . '=' . $this->_object->quote($key[$single_key]);
        }
        $where_str = join(' AND ', $where);
        $result = $this->_object->delete($where_str);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
