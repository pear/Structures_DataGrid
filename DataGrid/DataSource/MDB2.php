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

require_once 'MDB2.php';
require_once 'Structures/DataGrid/DataSource.php';


/**
 * PEAR::MDB2 Data Source Driver
 *
 * This class is a data source driver for the PEAR::MDB2 object
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_MDB2
    extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the PEAR::MDB2 object
     *
     * @var object MDB2
     * @access private
     */
    var $_db;

    /**
     * The query string
     *
     * @var string
     * @access private
     */
    var $_query;

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
    function Structures_DataGrid_DataSource_MDB2()
    {
        parent::Structures_DataGrid_DataSource();
        
        // FIXME: For clarity, supported options should be declared with 
        // _addDefaultOptions()
        
        $this->_setFeatures(array('multiSort' => true));
    }
  
    /**
     * Bind
     *
     * @param   string    $query     The query string
     * @param   mixed     $options   array('dbc' => [PEAR::MDB2 object])
     *                               or
     *                               array('dsn' => [PEAR::MDB2 dsn string])
     * @access  public
     * @return  mixed                True on success, PEAR_Error on failure
     */
    function bind($query, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        }

        if (isset($this->_options['dbc']) &&
            MDB2::isConnection($this->_options['dbc'])) {
            $this->_db = &$this->_options['dbc'];
        } elseif (isset($this->_options['dsn'])) {
            $dbOptions = array();
            if (array_key_exists('db_options', $options)) {
                $dbOptions = $options['db_options'];
            }
            $this->_db =& MDB2::factory($this->_options['dsn'], $dbOptions);
            if (PEAR::isError($this->_db)) {
                return PEAR::raiseError('Could not create connection: ' .
                                        $this->_db->getMessage() . ', ' .
                                        $this->_db->getUserInfo());
            }
        } else {
            return PEAR::raiseError('No MDB2 object or dsn string specified');
        }

        $this->_db->loadModule('Extended', null, false);

        if (is_string($query)) {
            $this->_query = $query;
            return true;
        } else {
            return PEAR::raiseError('Query parameter must be a string');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Offset (starting from 0)
     * @param   integer $limit      Limit
     * @access  public
     * @return  mixed               The 2D Array of the records on success,
                                    PEAR_Error on failure
    */
    function &fetch($offset=0, $limit=null)
    {
        if (!empty($this->_sortSpec)) {
            foreach ($this->_sortSpec as $field => $direction) {
                $sortArray[] = "$field $direction";
            }
            $sortString = ' ORDER BY '. join(', ', $sortArray);
        } else {
            $sortString = '';
        }

        $query = $this->_query;

        // drop LIMIT statement
        $query = preg_replace('#LIMIT\s.*$#isD', '', $query);

        // add or overwrite ORDER BY statement
        if (preg_match('#ORDER\s*BY#is', $query) === 0) {
            $query .= $sortString;
        } else {
            $query = preg_replace('#ORDER\s*BY\s.*$#isD',
                                  $sortString,
                                  $query);
        }

        if (is_null($limit)) {
            $result = $this->_db->query($query);
        } else {
            $result = $this->_db->extended->limitQuery($query, null, $limit, $offset);
        }

        if (PEAR::isError($result)) {
            return $result;
        }

        $recordSet = array();

        // Fetch the Data
        if ($numRows = $result->numRows()) {
            while ($record = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
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
     * @return  mixed       The number or records (int),
                            PEAR_Error on failure
    */
    function count()
    {
        if (array_key_exists('count_query', $this->_options)) {
            // complex queries might require special queries to get the
            // right row count
            $count = $this->_db->extended->getOne($this->_options['count_query']);
            // $count has an integer value with number of rows or is a
            // PEAR_Error instance on failure
        }
        elseif (preg_match('#GROUP\s*BY#is', $this->_query) === 1) {
            // GROUP BY is a special case
            // ==> use the normal query and then numRows()
            $result = $this->_db->query($this->_query);
            if (PEAR::isError($result)) {
                return $result;
            }
            $count = $result->numRows();
        } else {
            // don't query the whole table, just get the number of rows
            $query = preg_replace('#SELECT\s.*\sFROM#is',
                                  'SELECT COUNT(*) FROM',
                                  $this->_query);
            $count = $this->_db->extended->getOne($query);
            // $count has an integer value with number of rows or is a
            // PEAR_Error instance on failure
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


}
?>
