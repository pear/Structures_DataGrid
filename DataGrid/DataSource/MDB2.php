<?php
/**
 * PEAR::MDB2 SQL Query Data Source Driver
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
 * @category Structures
 * @package  Structures_DataGrid_DataSource_MDB2
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'MDB2.php';
require_once 'Structures/DataGrid/DataSource.php';

/**
 * PEAR::MDB2 SQL Query Data Source Driver
 *
 * This class is a data source driver for the PEAR::MDB2 object
 *
 * SUPPORTED OPTIONS:
 * 
 * - dbc:         (object) A PEAR::MDB2 instance that will be used by this
 *                         driver. Either this or the 'dsn' option is required.
 * - dsn:         (string) A PEAR::MDB2 dsn string. The MDB2 connection will be
 *                         established by this driver. Either this or the 'dbc'
 *                         option is required.
 * - db_options:  (array)  Options for the created database object. This option
 *                         is only used when the 'dsn' option is given.
 * - count_query: (string) Query that calculates the number of rows. See below
 *                         for more information about when such a count query
 *                         is needed.
 * 
 * GENERAL NOTES:
 *
 * You need to specify either a MDB2 instance or a MDB2 compatible dsn string as
 * an option to use this driver.
 * 
 * If you use complex queries (e.g. with complex joins or with aliases),
 * $datagrid->getRecordCount() might return a wrong result. For the case of
 * GROUP BY, UNION, or DISTINCT in your queries, and for the case of subqueries,
 * this driver already has special handling. However, if you observe wrong
 * record counts, you need to specify a special query that returns only the
 * number of records (e.g. 'SELECT COUNT(*) FROM ...') as an additional option
 * 'count_query' to the bind() call.
 * 
 * You can specify an ORDER BY statement in your query. Please be aware that this
 * sorting statement is then used in *every* query before the sorting options
 * that come from a renderer (e.g. by clicking on the column header when using
 * the HTML_Table renderer which is sent in the HTTP request).
 * If you want to give a default sorting statement that is only used if there is
 * no sorting query in the HTTP request, then use $datagrid->setDefaultSort().
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @access   public
 * @package  Structures_DataGrid_DataSource_MDB2
 * @category Structures
 */
class Structures_DataGrid_DataSource_MDB2
    extends Structures_DataGrid_DataSource
{   
    /**
     * Constructor
     *
     * @access public
     */
    function Structures_DataGrid_DataSource_MDB2()
    {
        parent::Structures_DataGrid_DataSource();
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
    function bind($query, $options = array())
    {
        $r = $this->_sqlBind($query, $options);
        if (!PEAR::isError($r)) {
            $this->_sqlHandle->loadModule('Extended', null, false);
        }
        return $r;
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Offset (starting from 0)
     * @param   integer $limit      Limit
     * @access  public
     * @return  mixed               The 2D Array of the records on success,
     *                              PEAR_Error on failure
    */
    function &fetch($offset = 0, $limit = null)
    {
        $recordSet = $this->_sqlFetch($offset, $limit);
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
        return $this->_sqlCount();
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
        $this->_sqlSort($sortSpec, $sortDir);
    }

    function &_connect()
    {
        return MDB2::connect($this->_options['dsn'], $this->_options['db_options']);
    }

    function _isConnection($dbc)
    {
        return MDB2::isConnection($dbc);
    }

   
    function _getRecords($query, $limit, $offset)
    {
        if (is_null($limit)) {
            if ($offset == 0) {
                $result = $this->_sqlHandle->query($query);
            } else {
                $result = $this->_sqlHandle->extended->limitQuery($query, null, 
                                PHP_INT_MAX, $offset);
            }
        } else {
            $result = $this->_sqlHandle->extended->limitQuery($query, null, 
                            $limit, $offset);
        }

        if (PEAR::isError($result)) {
            return $result;
        }

        $recordSet = array();

        // Fetch the Data
        if ($result->numRows()) {
            while ($record = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                $recordSet[] = $record;
            }
        }

        $result->free();

        return $recordSet;
    }

    function _quoteIdentifier($field)
    {
        return $this->_sqlHandle->quoteIdentifier($field);
    }

    function _getOne($query)
    {
        return $this->_sqlHandle->extended->getOne($query);
    }

    function _getRecordsNum($query)
    {
        $result = $this->_sqlHandle->query($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        return $result->numRows();
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
