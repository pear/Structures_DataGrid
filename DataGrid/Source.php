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

define('DATAGRID_SOURCE_ARRAY',         'Array');
define('DATAGRID_SOURCE_DATAOBJECT',    'DataObject');
define('DATAGRID_SOURCE_DB',            'DB');
define('DATAGRID_SOURCE_XML',           'XML');

/**
* Base abstract class for data source drivers
*
* Users may want to see the create() factory method
*
* Developers :
*
* <b>HOWTO develop a new source driver</b>
*
* Create a file for your new driver. For example :
*
*     Structures/DataGrid/Source/Foo.php
*
* In there, subclass this Structures_DataGrid_Source class :
* <code>
* class Structures_DataGrid_Source_Foo extends Structures_DataGrid_Source
* </code>
*
* In the constructor, initialize default options . These defaults will be
* used to validate user provided options, so you need to set all possible
* ones
* <code>
*     function Structures_DataGrid_Source_Foo()
*     {
*         parent::Structures_DataGrid_Source(); // required
*         $this->_addDefaultOptions(array( 'bar' => true));
*     }
* </code>
*
* Expose the sort(), limit(), fetch(), count() and bind() methods,
* overloading the provided skeleton. See the corresponding prototypes
* for more information on how to do this.
*
* Eventually, use the dump() debugging method to test your brand new
* driver
*
* There are some cases where you will want to use the
* Structures_DataGrid_Source_Array as base class instead of this one.
* See the Structures_DataGrid_Source_xml class for an example on how
* to do this.
*
* @author   Olivier Guilyardi <olivier@samalyse.com>
* @package  Structures_DataGrid
* @category Structures
* @version  $Revision $
*/
class Structures_DataGrid_DataSource
{
    /**
     * Common and driver-specific options
     *
     * @var array
     * @access protected
     * @see Structures_DataGrid_Source::setOption()
     * @see Structures_DataGrid_Source::addDefaultOptions()
     */
    var $_options = array();

    /**
     * Constructor
     *
     */
    function Structures_DataGrid_DataSource()
    {
    }

    /**
     * Adds some default options.
     *
     * This method is meant to be called by drivers. It allows adding some
     * default options. Additionally to setting default values the options
     * names (keys) are used by setOption() to validate its input.
     *
     * @access protected
     * @param array $options An associative array of the from:
     *                       array(optionName => optionValue, ...)
     * @return void
     * @see Structures_DataGrid_Source::setOption
     */
    function _addDefaultOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }
   
    /**
     * Driver Factory
     *
     * A clever method which loads and instantiate data source drivers.
     *
     * Basic Example :
     * <code>  
     * require_once 'Structures/DataGrid/Source';
     * $source =& Structures_DataGrid_Source::create($array,DATAGRID_SOURCE_ARRAY);
     * </code>
     *
     * This is stricly equivalent to :
     * <code>  
     * require_once 'Structures/DataGrid/Source/array.php
     * $source = new Structures_DataGrid_Source_Array();
     * $source->bind($array);
     * </code>
     *
     * This factory method is not considered to be the preferred way to load a
     * driver. Both of the above methods are valid, choice is yours.
     *
     * But, it provides a handy shortcut for setting options. For example :
     * <code>
     * require_once 'Structures/DataGrid/Source';
     * $source =& Structures_DataGrid_Source::create($array,DATAGRID_SOURCE_ARRAY,$options);
     * </code>
     *
     * And, it is clever, it can analyse its input and load the right
     * driver. This feature implies a little overhead, but allows simplifying
     * code by making the following statement :
     * <code>
     * require_once 'Structures/DataGrid/Source';
     * $source =& Structures_DataGrid_Source::create($array);
     * </code>
     *
     * which will detect that its source is an array and automatically load the
     * array driver with its default options.
     *
     * @access  public
     * @param   mixed   $source     The data source respective to the driver
     * @param   string  $type       The optional data source type constant
     * @param   array   $options    An associative array of the from:
     *                              array(optionName => optionValue, ...)
     * @uses    Structures_DataGrid_Source::_detectSourceType()
     * @return  mixed               Returns the source driver object or 
     *                              PEAR_Error on failure
     * @static
     */
    function &create($source, $type=null, $options=array())
    {
        if (is_null($type) &&
            !($type = Structures_DataGrid_DataSource::_detectSourceType($source))) {
            return new PEAR_Error('Unable to determine the data source type. '.
                                  'You may want to explicitly specify it.');
        }

        if (!@include_once "Structures/DataGrid/DataSource/$type.php") {
            return new PEAR_Error("No such data source driver: '$type'");
        }
        $classname = "Structures_DataGrid_DataSource_$type";
        $driver = new $classname();
       
        $test = $driver->setOption($options);
        if (PEAR::isError($test)) {
            return $test;
        }
       
        // Is this necessary?
        $test = $driver->bind($source);

        return PEAR::isError($test) ? $test : $driver;
    }
    

    /**
     * Set options
     *
     * This method can be called either to set a single options a in :
     * <code>$source->setOption($optionName,$optionValue)</code>
     * Or with an associative array to set multiple options :
     * <code>$source->setOption($options)</code>
     *
     * Note : should be called before bind()
     *
     * @param   mixed   $name   An option name string or an associative array
     *                          of the form :
     *                          array("option_name" => "option_value",...)
     * @param   mixed   $value  The optional value if parameter $name is a 
     *                          string
     * @return  mixed           true or a PEAR_error object upon failure
     * @access  public
     */
    function setOption($name, $value=null)
    {
        if (is_null($value) and is_array($name)) {
            // Handle setting multiple options when passed an associative array
            foreach ($name as $key => $val) {
                if (!isset($this->_options[$key])) {
                    return new PEAR_error("No such option : '$key'");
                }
                $this->_options[$key] = $val;
            }
        } else {
            if (!isset($this->_options[$name])) {
                return new PEAR_error("No such option : '$name'");
            }
            $this->_options[$name] = $value;
        }
        
        return true;
    }

    // Begin driver method prototypes DocBook template
     
    /**#@+
     *
     * This method is public, but please note that it is not intended to be called by
     * user-space code. It is meant to be called by the main Structures_DataGrid
     * container.
     *
     * It is intended to be overloaded by drivers.
     */
    
    /**
     * Sorting method prototype
     *
     * When overloaded, should either return true or a PEAR_Error
     *
     * @param   string  $field      The field name to sort by
     * @param   string  $direction  Either ASC or DESC
     * @return  object PEAR_Error   An error with message
     *                              'No data source driver loaded'
     * @access public                          
     */
    function sort($field, $direction)
    {
        return new PEAR_Error('No data source driver loaded');
    }

    /**
     * Limiting method prototype
     *
     * When overloaded, should either return true or a PEAR_Error
     *
     * @param   string  $offset     The offset where to fetch data from,
     *                              starting from 0
     * @param   string  $length     The number of rows (optional)
     * @return  object PEAR_Error   An error with message
     *                              'No data source driver loaded'
     * @access public                          
     */
    function limit($offset, $length=null)
    {
        return new PEAR_Error("No data source driver loaded");
    }
   
    /**
     * Fetching method prototype
     *
     * When overloaded, should either return a PEAR_Error or a <b>reference</b>
     * to an array of the form :
     *    array($columns,$records)
     * where $columns is an array of Structures_DataGrid_Column objects and
     * $records an array of Structures_DataGrid_Record objects
     *
     * @return  object PEAR_Error   An error with message
     *                              'No data source driver loaded'
     * @access  public                          
     */
    function &fetch()
    {
        $err = new PEAR_Error("No data source driver loaded");
        return $err;
    }

    /**
     * Counting method prototype
     *
     * When overloaded, should either return a numeric value indicating the
     * total number of rows in the data source, or a PEAR_Error object
     *
     * @return  object PEAR_Error       An error with message
     *                                  'No data source driver loaded'
     * @access  public                          
     */
    function count()
    {
        return PEAR_Error("No data source driver loaded");
    }
  
    /**
     * Datasource binding method prototype
     *
     * When overloaded, should either return true or a PEAR_Error object
     *
     * @return  object PEAR_Error   An error with message
     *                              'No data source driver loaded'
     * @access  public                          
     */
    
    function bind()
    {
        return PEAR_Error("No data source driver loaded");
    }
  
    /**#@-*/

    // End DocBook template
   
    /**
     * Dump the data as returned by fetch().
     *
     * This method is meant for debugging purposes. It returns what fetch()
     * would return to its DataGrid host as a nicely formatted console-style
     * table.
     *
     * @return  string      The table string, ready to be printed
     * @uses    Structures_DataGrid_Source::fetch()
     * @access  public
     */
    function dump()
    {
        $data =& $this->fetch();
        list($columns,$records) = $data;

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
            $headers = array_keys($records[0]->getRecord());
        }

        $table->setHeaders($headers);
        
        foreach ($records as $rec) {
            $table->addRow($rec->getRecord());
        }
       
        return $table->getTable();
    }
   
    /**
     * Detect source type
     *
     * @param   mixed   $source     Some kind of source
     * @return  string              The type constant of this source or null if
     *                              it couldn't be detected
     * @access  private
     */
    function _detectSourceType($source)
    {
        switch($source) {
            // DB_DataObject
            case (is_subclass_of($source, 'db_dataobject')):
                return DATAGRID_SOURCE_DATAOBJECT;
                break;

            // DB_Result
            case (strtolower(get_class($source)) == 'db_result'):
                return DATAGRID_SOURCE_DBRESULT;
                break;
                
            // Array
            case (is_array($source)):
                return DATAGRID_SOURCE_ARRAY;
                break;

            // XML
            case (is_string($source) and ereg('^ *<\?xml',$source)):
                return DATAGRID_SOURCE_XML;
                break;
                
            default:
                return null;
                break;
        }
    }
}
?>