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
// |         Olivier Guilyardi <olivier@samalyse.com>                     |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'Structures/DataGrid/DataSource/Array.php';

/**
 * PEAR::DB Data Source Driver
 *
 * This class is a data source driver for the PEAR::DB::DB_Result object
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com> 
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_DB
    extends Structures_DataGrid_DataSource_Array
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
        parent::Structures_DataGrid_DataSource_Array();
    }
  
    /**
     * Bind
     *
     * @param   object DB_Result    The result object to bind
     * @access  public
     * @return  mixed               True on success, PEAR_Error on failure
     */
    function bind(&$result, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        if (strtolower(get_class($result)) == 'db_result') { 
            while ($record = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
                $this->_ar[] = $record;
            }
            return true;
        } else {
            return PEAR::raiseError('The provided source must be a DB_Result');
        }
    }

}
?>