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
// | Authors: Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Andrew Nagy <asnagy@webitecture.org>                        |
// +----------------------------------------------------------------------+
//
// $Id$


/**
* Base abstract class for data source drivers
* 
* <b>Recognized options (valid for all drivers) :</b>
*
* <b>"generate_columns" </b> Generate 
* Structures_DataGrid_Column objects with labels. (default : false)
* 
* <b>"labels" : </b> How to translate the field names to column labels. 
* Only used when "generate_columns" is true. Default : array().
* This is an associative array of the form :
* <code> 
* array ("fieldName" => "fieldLabel", ...) 
* </code>
* 
* <b>"fields" : </b> Which fields should be rendered (Only used when
* "generate_columns" is true. The default is an empty array : all of
* the DataObject's fields will be rendered.
* This is an array of the form :
* <code>
* array ("fieldName1", "fieldName2", ...)
* </code>
* 
* Users may want to see the create() factory method
*
* Developers :
*
* <b>HOWTO develop a new source driver</b>
*
* Subclass this Structures_DataGrid_DataSource class :
* <code>
* class Structures_DataGrid_DataSource_Foo extends
*     Structures_DataGrid_DataSource
* </code>
*
* In the constructor, initialize default options . These defaults will be
* used to validate user provided options, so you need to set all possible
* ones
* <code>
*     function Structures_DataGrid_DataSource_Foo()
*     {
*         parent::Structures_DataGrid_DataSource(); // required
*         $this->_addDefaultOptions(array( 'bar' => true));
*     }
* </code>
*
* Expose the fetch(), count() and bind() methods, overloading 
* the provided skeleton. See the corresponding prototypes
* for more information on how to do this.
*
* Do not forget to call Structures_DataGrid_DataSource::setOptions()
* from your bind() method.
* ex : if ($options) $this->setOptions($options);
*
* Eventually, use the dump() debugging method to test your brand new
* driver
*
* @author   Olivier Guilyardi <olivier@samalyse.com>
* @author   Andrew Nagy <asnagy@webitecture.org>
* @author   Mark Wiesemann <wiesemann@php.net>
* @package  Structures_DataGrid
* @category Structures
* @version  $Revision $
*/
class Structures_DataGrid_DataSource
{
    /* FIXME: Did we really need to rename this class from DataSource to 
     * DataSource_Common ? IIRC we did this to mimic the MDB2 internals, 
     * which is pointless. There are some good practices in MDB2, that
     * we have tried to implement in the Renderer layer. But the "Common" 
     * suffix is convention that we do not need to follow.
     *
     * It will break BC for no reason. for people who have written their own 
     * drivers by subclassing the old DataSource class.
     *
     * This is fixed... 
     */
     
    /**
     * Common and driver-specific options
     *
     * @var array
     * @access protected
     * @see Structures_DataGrid_DataSource::_setOption()
     * @see Structures_DataGrid_DataSource::addDefaultOptions()
     */
    var $_options = array();

    /**
     * Special driver features
     *
     * @var array
     * @access protected
     */
    var $_features = array();
    
    /**
     * Constructor
     *
     */
    function Structures_DataGrid_DataSource()
    {
        $this->_options = array('generate_columns' => false,
                                'labels'           => array(),
                                'fields'           => array());

        $this->_features = array(
                'multiSort' => false, // Multiple field sorting
        );
    }

    /**
     * Adds some default options.
     *
     * This method is meant to be called by drivers. It allows adding some
     * default options. 
     *
     * @access protected
     * @param array $options An associative array of the form:
     *                       array(optionName => optionValue, ...)
     * @return void
     * @see Structures_DataGrid_DataSource::_setOption
     */
    function _addDefaultOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Add special driver features
     *
     * This method is meant to be called by drivers. It allows specifying 
     * the special features that are supported by the current driver.
     *
     * @access protected
     * @param array $features An associative array of the form:
     *                        array(feature => true|false, ...)
     * @return void
     */
    function _setFeatures($features)
    {
        $this->_features = array_merge($this->_features, $features);
    }
    
    /**
     * Set options
     *
     * @param   mixed   $options    An associative array of the form :
     *                              array("option_name" => "option_value",...)
     * @access  protected
     */
    function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Generate columns if options are properly set
     *
     * Note : must be called after fetch()
     * 
     * @access public
     * @return array Array of Column objects. Empty array if irrelevant.
     */
    function getColumns()
    {
        $columns = array();
        if ($this->_options['generate_columns'] 
            and $fieldList = $this->_options['fields']) {
                             
            include_once('Structures/DataGrid/Column.php');
            
            foreach ($fieldList as $field) {
                $label = strtr($field, $this->_options['labels']);
                $col = new Structures_DataGrid_Column($label, $field, $field);
                $columns[] = $col;
            }
        }
        
        return $columns;
    }
    
    
    // Begin driver method prototypes DocBook template
     
    /**#@+
     * 
     * This method is public, but please note that it is not intended to be 
     * called by user-space code. It is meant to be called by the main 
     * Structures_DataGrid class.
     *
     * It is an abstract method, part of the DataGrid Datasource driver 
     * interface, and must overloaded by drivers.
     */
   
    /**
     * Fetching method prototype
     *
     * When overloaded this method must return a 2D array of records 
     * on success or a PEAR_Error object on failure.
     *
     * @abstract
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortSpec   If the driver supports the "multiSort" 
     *                              feature this can be either a single field 
     *                              (string), or a sort specification array of 
     *                              the form : array(field => direction, ...)
     *                              If "multiSort" is not supported, then this
     *                              can only be a string.
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @return  object              PEAR_Error with message 
     *                              "No data source driver loaded" 
     * @access  public                          
     */
    function &fetch($offset=0, $len=null, $sortSpec=null, $sortDir='ASC')
    /* FIXME: Why is there these $sortField and $sortDir parameters ? This
     * is redundant with the sort() method.
     */
    {
        return new PEAR_Error("No data source driver loaded");
    }

    /**
     * Counting method prototype
     *
     * Note : must be called before fetch() 
     *
     * When overloaded, this method must return the total number or records 
     * or a PEAR_Error object on failure
     * 
     * @abstract
     * @return  object              PEAR_Error with message 
     *                              "No data source driver loaded" 
     * @access  public                          
     */
    function count()
    {
        return new PEAR_Error("No data source driver loaded");
    }
    
    /**
     * Sorting method prototype
     *
     * When overloaded this method must return true on success or a PEAR_Error 
     * object on failure.
     * 
     * Note : must be called before fetch() 
     * 
     * @abstract
     * @param   string  $sortSpec   If the driver supports the "multiSort" 
     *                              feature this can be either a single field 
     *                              (string), or a sort specification array of 
     *                              the form : array(field => direction, ...)
     *                              If "multiSort" is not supported, then this
     *                              can only be a string.
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @return  object              PEAR_Error with message 
     *                              "No data source driver loaded" 
     * @access  public                          
     */
    function sort($sortSpec, $sortDir = null)
    {
        return new PEAR_Error("No data source driver loaded");
    }    
  
    /**
     * Datasource binding method prototype
     *
     * When overloaded this method must return true on success or a PEAR_Error 
     * object on failure.
     *
     * @abstract
     * @param   mixed $container The datasource container
     * @param   array $options   Binding options
     * @return  object              PEAR_Error with message 
     *                              "No data source driver loaded" 
     * @access  public                          
     */
    function bind($container, $options = array())
    {
        return new PEAR_Error("No data source driver loaded");
    }
  
    /**#@-*/

    // End DocBook template
  
    /**
     * List special driver features
     *
     * @return array Of the form: array(feature => true|false, etc...)
     * @access public
     */
    function getFeatures()
    {
        return $this->_features;
    }
   
    /**
     * Tell if the driver as a specific feature
     *
     * @param  string $name Feature name
     * @return bool 
     * @access public
     */
    function hasFeature($name)
    {
    }
    
    /**
     * Dump the data as returned by fetch().
     *
     * This method is meant for debugging purposes. It returns what fetch()
     * would return to its DataGrid host as a nicely formatted console-style
     * table.
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @return  string              The table string, ready to be printed
     * @uses    Structures_DataGrid_DataSource::fetch()
     * @access  public
     */
    function dump($offset=0, $len=null, $sortField=null, $sortDir='ASC')
    {
        $records =& $this->fetch($offset, $len, $sortField, $sortDir);
        $columns = $this->getColumns();

        if (!$columns and !$records) {
            return "<Empty set>\n";
        }
        
        include_once 'Console/Table.php';
        $table = new Console_Table();
        
        $headers = array();
        if ($columns) {
            foreach ($columns as $col) {
                $headers[] = is_null($col->fieldName)
                            ? $col->columnName
                            : "{$col->columnName} ({$col->fieldName})";
            }
        } else {
            $headers = array_keys($records[0]);
        }

        $table->setHeaders($headers);
        
        foreach ($records as $rec) {
            $table->addRow($rec);
        }
       
        return $table->getTable();
    }
  
}
?>
