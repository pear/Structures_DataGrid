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

require_once 'Structures/DataGrid/Source.php';

/**
 * PEAR::DB_DataObject Data Source Driver
 *
 * This class is a data source driver for a PEAR::DB::DB_DataObject object
 *
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_DataObject extends Structures_DataGrid_Source
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
        $this->_addDefaultOptions(array('generate_columns' => true,
                                        'labels_property' => 
                                            'fb_fieldLabels',
                                        'render_property' => 
                                            'fb_fieldsToRender'));
    }
  
    /**
     * Bind
     *
     * @param   object DB_DataObject    The DB_DataObject object to bind
     * @access  public
     * @return  mixed   True on success, PEAR_Error on failure
     */
    function bind(&$dataobject)
    {
        if (is_subclass_of($dataobject, 'DB_DataObject')) {
            $this->_dataobject =& $dataobject;
            return true;
        } else {
            return new PEAR_Error('The provided source must be a DB_DataObject');
        }
    }

    /**
     * Sort
     *
     * @access  public
     * @param   string $field       The field to sort by
     * @param   string $direction   The direction to sort, either ASC or DESC
     */    
    function sort($field, $direction='ASC')
    {
        $this->_dataobject->orderBy("$field $direction");
    }

    /**
     * Limit
     *
     * @access  public
     * @param   int $offset     The count offset
     * @param   int $length     The amount to limit to
     */         
    function limit($offset, $length)
    {
        if ($offset) {
            $this->_dataobject->limit($offset, $length);
        } else {
            $this->_dataobject->limit($length);
        }
    }
    
    /**
     * Fetch
     *
     * @access  public
     * @return  array       The 2D Array of the records
     */    
    function &fetch()
    {
        $columns = array();
        $records = array();

        /* Auto generating columns if required */
        if ($this->_options['generate_columns']) {
           
            if ($fRender = $this->_options['render_property']) {
                $fList = @$this->_dataobject->$fRender;
            }
            
            if (!$fList) $fList = array_keys($this->_dataobject->toArray());

            $labelVar = $this->_options['labels_property'];
            $field2label = @$this->_dataobject->$labelVar
            or $field2label = array();

            include_once('Structures/DataGrid/Column.php');
            
            foreach ($fList as $field) {
                $label = strtr($field,$field2label);
                $col = new Structures_DataGrid_Column($label,$field,$field);
                $columns[] = $col;
            }

        }

        include_once 'Structures/DataGrid/Record/DataObject.php';

        if ($this->_dataobject->find()) {
            while ($this->_dataobject->fetch()) {
                $records[] = new Structures_DataGrid_Record_DataObject
                              ($this->_dataobject);
            }
        } else {
            return new PEAR_Error('Couldn\'t fetch data');
        }
       
        return array('Columns' => $columns, 'Records' => $records);
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number or records
     */    
    function count()
    {
        return $this->_dataobject->N;
        //return $this->_dataobject->count();
    }


}
?>