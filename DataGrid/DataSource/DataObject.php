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
// |         Andrew Nagy <asnagy@webitecture.org>                         |
// +----------------------------------------------------------------------+
//
// $Id $

require_once 'Structures/DataGrid/Source.php';

/**
 * PEAR::DB_DataObject Data Source Driver
 *
 * This class is a data source driver for a PEAR::DB::DB_DataObject object
 *
 * Recognized options :
 *
 * <b>"labels_property" : </b> The name of a property that you can set
 * within your DataObject. This property is expected to contain the
 * same kind of information as the "labels" options. If the "labels" 
 * option is set, this one will not be used. Default : "fb_fieldsLabels".
 *
 * <b>"fields_property" : </b> The name of a property that you can set
 * within your DataObject. This property is expected to contain the
 * same kind of information as the "fields" options. If the "fields"
 * option is set, this one will not be used. Default : "fb_fieldsToRender".
 * 
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_DataObject
    extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the DataObject
     *
     * @var object DB_DataObject
     * @access private
     */
    var $_dataobject;
    
    /**
     * Constructor
     *
     * @param object DB_DataObject
     * @access public
     */
    function Structures_DataGrid_DataSource_DataObject()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array(
                    'labels_property' => 'fb_fieldLabels',
                    'fields_property' => 'fb_fieldsToRender'));
    }
  
    /**
     * Bind
     *
     * @param   object DB_DataObject    $dataobject     The DB_DataObject object
     *                                                  to bind
     * @param   array                   $options        Associative array of 
     *                                                  options.
     * @access  public
     * @return  mixed   True on success, PEAR_Error on failure
     */
    function bind(&$dataobject, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        }

        if (is_subclass_of($dataobject, 'DB_DataObject')) {
            $this->_dataobject =& $dataobject;

            $mergeOptions = array();
            
            // Merging the fields and fields_property options
            if (!$this->_options['fields']) {
                if ($fieldsVar = $this->_options['fields_property']
                    and isset($this->_dataobject->$fieldsVar)) {
                    
                    $mergeOptions['fields'] = $this->_dataobject->$fieldsVar;    
                } else {
                    $mergeOptions['fields'] = 
                        array_keys($this->_dataobject->toArray());
                }
            }

            // Merging the labels and labels_property options
            if (!$this->_options['labels'] 
                and $labelsVar = $this->_options['labels_property']
                and isset($this->_dataobject->$labelsVar)) {
                
                $mergeOptions['labels'] = $this->_dataobject->$labelsVar;

            }

            if ($mergeOptions) {
                $this->setOptions($mergeOptions);
            }
                
            return true;
        } else {
            return new PEAR_Error('The provided source must be a DB_DataObject');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @access  public
     * @return  array   The 2D Array of the records
     */    
    function &fetch($offset=0, $len=null, $sortField=null, $sortDir='ASC')
    {
        // Sorting
        if ($sortField) {
            $this->_dataobject->orderBy("$sortField $sortDir");
        }
        
        // Limiting
        if ($offset) {
            $this->_dataobject->limit($offset, $len);
        } elseif ($len) {
            $this->_dataobject->limit($len);
        }
        
        $records = array();

        // Retrieving data
        if ($this->_dataobject->find()) {
            while ($this->_dataobject->fetch()) {
                // Only retrieve the fields to render, as set in $fList :
                $rec = array();
                foreach ($this->_options['fields'] as $fName) {
                    $rec[$fName] = $this->_dataobject->$fName;
                }
                $records[] = $rec;
            }
        } else {
            return new PEAR_Error('Couldn\'t fetch data');
        }
       
        return $records;
    }

    /**
     * Count
     *
     * NOTE: This has to be called before fetch() !!
     * This may be a DataObject bug. See test_dataobject_options.php. 
     * 
     * @access  public
     * @return  int         The number or records
     */    
    function count()
    {
        return $this->_dataobject->count();
    }

}
?>