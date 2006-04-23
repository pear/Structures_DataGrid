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

require_once 'Structures/DataGrid/DataSource/Array.php';

/**
 * Comma Seperated Value (CSV) Data Source Driver
 *
 * This class is a data source driver for a CSV File.  It will also support any
 * other delimiter.
 *
 * @version  $Revision$
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_CSV extends
    Structures_DataGrid_DataSource_Array
{
    function Structures_DataGrid_DataSource_CSV()
    {
        parent::Structures_DataGrid_DataSource_Array();
        $this->_addDefaultOptions(array('delimiter' => ','));
    }

    /**
     * Bind
     *
     * @param   mixed $csv      Can be either the path to the CSV file or a
     *                          string containing the CSV data
     * @access  public
     * @return  mixed           True on success, PEAR_Error on failure
     */    
    function bind($csv, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        if (@is_file($csv)) {
            if (!$rowList = file($csv)) {
                return PEAR::raiseError('Could not read file');
            }
        } else {
            $rowList = explode("\n", $csv);
        }

        foreach ($rowList as $row) {
            $row = trim($row); // to remove DOSish \r
            $this->_ar[] = explode($this->_options['delimiter'], $row);
        }
        
        return true;
    }
}

?>
