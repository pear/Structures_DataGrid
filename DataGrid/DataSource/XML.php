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

require_once 'Structures/DataGrid/DataSource/Array.php';
require_once 'XML/Unserializer.php';

/**
 * XML data source driver
 *
 * This driver accepts the following options :
 *
 * <b>"xpath" : </b> XPath to a subset of the XML data.
 *
 * @package Structures_DataGrid
 * @author Olivier Guilyardi <olivier@samalyse.com>
 * @category Structures
 * @version  $Revision $
 */
class Structures_DataGrid_DataSource_XML extends Structures_DataGrid_DataSource
{
    var $_ar = array();

    /**
     * Constructor
     * 
     */
    function Structures_DataGrid_DataSource_XML()
    {
        $this->Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array('xpath' => ''));
    }

    /**
     * Bind XML data 
     * 
     * @access public
     * @param string $xml     XML data
     * @param array  $options Options as an associative array
     * @return void on success, PEAR_Error on failure 
     */
    function bind($xml, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        // Extract a subset from the XML data if an XPath is provided :
        if ($this->_options['xpath']) {
            include_once 'XML/XPath.php';
            $xpath = new XML_XPath($xml);
            $result =& $xpath->evaluate($this->_options['xpath']); 
            if (PEAR::isError($result)) {
                return $result;
            }
            $xml = '';
            while ($result->next()) {
                $xml .= $result->toString(null, false, false);
            }   
            
        }
       
        // Unserialize that up :
        $unserializer = &new XML_Unserializer();
        $test = $unserializer->unserialize($xml, false); 
        
        if (PEAR::isError($test)) {
            return $test;
        }
        
        $data = $unserializer->getUnserializedData();

        list($junk,$data) = each($data);

        // Build a simple array  :
        $this->_ar = array();
        foreach ($data as $index => $row)
        {
            if (!is_array($row) or !is_numeric($index)) {
                return new PEAR_Error('Unable to bind the xml data. '.
                                      'You may want to set the \'xpath\' option.');
            }

            $this->_ar[] = $row;
        }

        if ($this->_ar and !$this->_options['fields']) {
            $this->_setOptions(array('fields' => array_keys($this->_ar[0])));
        }

        
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