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
// | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
// |          Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Mark Wiesemann <wiesemann@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'PEAR.php';

require_once 'Structures/DataGrid/Column.php';

// Renderer Drivers
define ('DATAGRID_RENDER_TABLE',    'HTMLTable');
define ('DATAGRID_RENDER_SMARTY',   'Smarty');
define ('DATAGRID_RENDER_XML',      'XML');
define ('DATAGRID_RENDER_XLS',      'XLS');
define ('DATAGRID_RENDER_XUL',      'XUL');
define ('DATAGRID_RENDER_CSV',      'CSV');
define ('DATAGRID_RENDER_CONSOLE',  'Console');

// Data Source Drivers
define('DATAGRID_SOURCE_ARRAY',     'Array');
define('DATAGRID_SOURCE_DATAOBJECT','DataObject');
define('DATAGRID_SOURCE_DB',        'DB');
define('DATAGRID_SOURCE_XML',       'XML');
define('DATAGRID_SOURCE_RSS',       'RSS');
define('DATAGRID_SOURCE_CSV',       'CSV');
define('DATAGRID_SOURCE_DBQUERY',   'DBQuery');
define('DATAGRID_SOURCE_DBTABLE',   'DBTable');
define('DATAGRID_SOURCE_MDB2',      'MDB2');

/**
 * Structures_DataGrid Class
 *
 * A PHP class to implement the functionality provided by the .NET Framework's
 * DataGrid control.  This class can produce a data driven list in many formats
 * based on a defined record set.  Commonly, this is used for outputting an HTML
 * table based on a record set from a database or an XML document.  It allows
 * for the output to be published in many ways, including an HTML table,
 * an HTML Template, an Excel spreadsheet, an XML document.  The data can
 * be sorted and paged, each cell can have custom output, and the table can be
 * custom designed with alternating color rows.
 *
 * Quick Example:
 * <code>
 * <?php
 * require('Structures/DataGrid.php');
 * $dg = new Structures_DataGrid();
 * $result = mysql_query('SELECT * FROM users');
 * while ($rs = mysql_fetch_assoc($result)) {
 *     $dataSet[] = $rs;
 * }
 * $dg->bind($dataSet);
 * echo $dg->render();
 * ?>
 * </code>
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid 
{
    /**
     * Renderer driver
     * @var object Structures_DataGrid_Renderer_* family
     * @access private
     */ 
    var $renderer;

    /**
     * Array of columns.  Columns are defined as a DataGridColumn object.
     * @var array
     * @access private
     */
    var $columnSet = array();

    /**
     * Array of records.  Records are defined as a DataGridRecord object.
     * @var array
     * @access private
     */
    var $recordSet = array();

    /**
     * The Data Source Driver object
     * @var object Structures_DataGrid_DataSource
     * @access private
     */
    var $_dataSource;    
    
    /**
     * An array of fields to sort by.  Each field is an array of the field name
     * and the direction, either ASC or DESC.
     * @var array
     * @access private
     */
    var $sortArray;

    /**
     * Limit of records to show per page.
     * @var string
     * @access private
     */
    var $rowLimit;

    /**
     * The current page to show.
     * @var string
     * @access private
     */
    var $page;

    /**
     * Whether the page number was provided at instantiation or not
     * @var bool
     * @access private
     */
    var $_forcePage; 
    
    /**
     * GET/POST/Cookie parameters prefix
     * @var string
     * @access private
     */
     var $_requestPrefix = '';    

     var $_isBuilt = false;
     
    /**
     * Constructor
     *
     * Builds the DataGrid class.  The Core functionality and Renderer are
     * seperated for maintainability and to keep cohesion high.
     *
     * @param  string   $limit      The row limit per page.
     * @param  int      $page       The current page viewed.
     *                              Note : if you specify this, the "page" GET 
     *                              variable will be ignored.
     * @param  string   $rendererType   The type of renderer to use.
     * @return void
     * @access public
     */
    function Structures_DataGrid($limit = null, $page = null,
                                 $rendererType = DATAGRID_RENDER_TABLE)
    {
        // Set the defined rowlimit
        $this->rowLimit = $limit;
        
        //Use set page number, otherwise automatically detect the page number
        if (!is_null ($page)) {
            $this->page = $page;
            $this->_forcePage = true;
        } else {
            $this->page = 1;
            $this->_forcePage = false;
        }

        // Automatic handling of GET/POST/COOKIE variables
        $this->_parseHttpRequest();

        $this->setRenderer ($rendererType);
    }

    /**
     * Method used for debuging purposes only.  Displays a dump of the DataGrid
     * object.
     *
     * @access  public
     * @return  void
     */
    function dump()
    {
        echo '<pre>';
        print_r($this);
        echo '</pre>';
    }

    /**
     * Checks if a file exists in the include path
     *
     * @access private
     * @param  string   filename
     * @return boolean true success and false on error
     */
    function fileExists($file)
    {
        $dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($dirs as $dir) {
            if (is_readable($dir . DIRECTORY_SEPARATOR . $file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Load a Renderer or DataSource driver
     * 
     * @param string $className Name of the driver class
     * @access private
     * @return object The driver object or a PEAR_Error
     */
    function &loadDriver($className)
    {
        if (!class_exists($className)) {
            $file_name = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            if (!include_once($file_name)) {
                if (!Structures_DataGrid::fileExists($file_name)) {
                    $msg = "unable to find package '$className' file '$file_name'";
                } else {
                    $msg = "unable to load driver class '$className' from file '$file_name'";
                }
                return new PEAR_Error($msg);
            }
        }

        $driver =& new $className();
        return $driver;
    }
    
    /**
     * Datasource driver Factory
     *
     * A clever method which loads and instantiate data source drivers.
     *
     * Can be called in various ways :
     *
     * Detect the source type and load the appropriate driver with default
     * options :
     * <code>
     * $driver =& Structures_DataGrid::datasourceFactory($source);
     * </code>
     *
     * Detect the source type and load the appropriate driver with custom
     * options :
     * <code>
     * $driver =& Structures_DataGrid::datasourceFactory($source, $options);
     * </code>
     *
     * Load a driver for an explicit type (faster, bypasses detection routine) :
     * <code>
     * $driver =& Structures_DataGrid::datasourceFactory($source, $options, $type);
     * </code>
     *
     * @access  private
     * @param   mixed   $source     The data source respective to the driver
     * @param   array   $options    An associative array of the form :
     *                              array(optionName => optionValue, ...)
     * @param   string  $type       The data source type constant (of the form 
     *                              DATAGRID_DATASOURCE_*)  
     * @uses    Structures_DataGrid_DataSource::_detectSourceType()     
     * @return  mixed               Returns the source driver object or 
     *                              PEAR_Error on failure
     * @static
     */
    function &datasourceFactory($source, $options=array(), $type=null)
    {
        if (is_null($type) &&
            !($type = Structures_DataGrid::_detectSourceType($source,
                                                             $options))) {
            $error = new PEAR_Error('Unable to determine the data source type. '.
                                    'You may want to explicitly specify it.');
            return $error;
        }

        $className = "Structures_DataGrid_DataSource_$type";

        if (PEAR::isError($driver =& $this->loadDriver($className))) {
            return $driver;
        }
        
        $result = $driver->bind($source, $options);
       
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $driver;
        }
    }

    /**
     * Renderer driver factory
     *
     * Load and instantiate a renderer driver.
     * 
     * @access  private
     * @param   mixed   $source     The rendering container respective to the driver
     * @param   array   $options    An associative array of the form :
     *                              array(optionName => optionValue, ...)
     * @param   string  $type       The renderer type constant (of the form 
     *                              DATAGRID_RENDER_*)  
     * @uses    Structures_DataGrid_DataSource::_detectRendererType()     
     * @return  mixed               Returns the renderer driver object or 
     *                              PEAR_Error on failure
     */
    function &rendererFactory($type, $options = array())
    {
        /*
        if (is_null($type) &&
            !($type = Structures_DataGrid::_detectRendererType($renderingContainer))) {
            return new PEAR_Error('Unable to determine the renderer type. '.
                                  'You may want to explicitly specify it.');
        }
        */

        // FIXME: any security risk here (like including /etc/passwd) ?
        $className = "Structures_DataGrid_Renderer_$type";

        if (PEAR::isError($driver =& $this->loadDriver($className))) {
            return $driver;
        }        
      
        if ($options) {
            $driver->setOptions($options);
        }

        if ($this->_requestPrefix) {
            $driver->setRequestPrefix($this->_requestPrefix); 
        }
        
        if (isset($this->sortArray)) {
            $driver->setCurrentSorting($this->sortArray[0], $this->sortArray[1]);
        }
        
        return $driver;
    }
    
    /**
     * Render the datagrid
     *
     * @param  string   $limit  The row limit per page.
     * @param  string   $page   The current page viewed.
     * @access public
     */
    function render()
    {
        if (!$this->_isBuilt) {
            $this->build();
        }
        $this->renderer->render();
    }

    /**
     * Return the datagrid output as a string
     *
     * @access public
     * @return string The datagrid output (HTML, CSV, etc...)
     */
    function getOutput()
    {
        if (!$this->_isBuilt) {
            $this->build();
        }
        return $this->renderer->getOutput();
    }
    
    /**
     * Get Renderer
     *
     * Retrieves the renderer object as a reference
     *
     * @access public
     */
    function &getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Set Renderer
     *
     * Defines which renderer to be used by the DataGrid
     *
     * @param  string   $renderer       The defined renderer string
     * @param  array    $options        Rendering options
     * @access public
     */
    function setRenderer($type, $options = array())
    {
        $renderer =& $this->rendererFactory($type, $options);
        if (!PEAR::isError($renderer)) {
            return $this->attachRenderer($renderer);
        } else {
            return $renderer;
        }
    }

    function attachRenderer($renderer)
    {
        if (is_subclass_of($renderer, 'structures_datagrid_renderer_common')) {
            $this->renderer =& $renderer;
            if (isset ($this->_dataSource)) {
                $this->renderer->setData($this->columnSet, $this->recordSet);
                $this->renderer->setLimit($this->page, $this->rowLimit, 
                                          $this->getRecordCount());
            }
        } else {
            return new PEAR_Error('Invalid renderer type, ' . 
                                  'must be a valid renderer driver class');
        }
        
        return true;
    }
    
    function renderInto (&$container)
    {
        $this->renderer->setContainer ($container);
    }
    
    /**
     * Set Default Headers
     *
     * This method handles determining if column headers need to be set.
     *
     * @access private
     */
    function _setDefaultHeaders()
    {
        /* FIXME: what the hell is this ?
         
          
        if ((!count($this->columnSet)) && (count($this->recordSet))) {
            $arrayKeys = array_keys($this->recordSet[0]);
            foreach ($arrayKeys as $key) {
                $width = ceil(100/count($arrayKeys));
                $column = new Structures_DataGrid_Column($key, $key, $key,
                                                         array('width' =>
                                                               $width.'%'));
                $this->addColumn($column);
            }
        }
        */
    }
    
    /**
     * Retrieves the current page number when paging is implemented
     *
     * @return string    the current page number
     * @access public
     */
    function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * Define the current page number.  This is used when paging is implemented
     *
     * @access public
     * @param  string    $page       The current page number.
     */
    function setCurrentPage($page)
    {
        $this->page = $page;
    }
    
    /**
     * Returns the total number of pages
     * (returns 0 if there are no records, returns 1 if there is no row limit)
     *
     * @return string    the total number of pages
     * @access public
     */
    function getPageCount()
    {
        if (is_null($this->rowLimit) || $this->getRecordCount() == 0) {
            return 1;
        } else {
            return ceil($this->getRecordCount() / $this->rowLimit);
        }
    }
    
    /**
     * Returns the total number of records
     *
     * @return string    the total number of records
     * @access public
     */
    function getRecordCount()
    {
        if (!is_null ($this->_dataSource)) {
            return $this->_dataSource->count();
        } else {
            return count($this->recordSet);
        }
    }    
    
    /**
     * Returns the number of the first record of the current page
     * (returns 0 if there are no records, returns 1 if there is no row limit)
     *
     * @return string    the number of the first record currently shown
     * @access public
     */
    function getCurrentRecordNumberStart()
    {
        if (is_null($this->page)) {
            return 1;
        } elseif ($this->getRecordCount() == 0) {
            return 0;
        } else {
            return ($this->page - 1) * $this->rowLimit + 1;
        }
    }

    /**
     * Returns the number of the last record of the current page
     *
     * @return string    the number of the last record currently shown
     * @access public
     */
    function getCurrentRecordNumberEnd()
    {
        if (is_null($this->rowLimit)) {
            return $this->getRecordCount();
        } else {
            return
                min($this->getCurrentRecordNumberStart() + $this->rowLimit - 1,
                    $this->getRecordCount());
        }
    }    
    
    /**
     * If you need to change the request variables, you can define a prefix.
     * This is extra useful when using multiple datagrids.
     *
     * @access  public
     * @param   string $prefix      The prefix to use on request variables;
     */
    function setRequestPrefix($prefix)
    {
        $this->_requestPrefix = $prefix;
        
        // Automatic handling of GET/POST/COOKIE variables
        $this->_parseHttpRequest();
    }    
    
    /**
     * Adds a DataGrid_Column object to this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Column   $column     The column
     *          object to add. This object should be a
     *          Structures_DataGrid_Column object.
     * @return  bool    True if successful, otherwise false.
     */
    function addColumn($column)
    {
        if (is_a($column, 'structures_datagrid_column')) {
            $this->columnSet = array_merge($this->columnSet, array($column));
            return true;
        } else {
            return false;
        }
    }

    /**
     * A simple way to add a record set to the datagrid
     *
     * @access  public
     * @param   mixed   $rs         The record set in any of the supported data
     *                              source types
     * @param   array   $options    Optional. The options to be used for the
     *                              data source
     * @param   string  $type       Optional. The data source type
     * @return  bool                True if successful, otherwise PEAR_Error.
     */
    function bind($rs, $options = array(), $type = null)
    {
        $source =& Structures_DataGrid::datasourceFactory($rs, $options, $type);
        if (!PEAR::isError($source)) {
            return $this->bindDataSource($source);
        } else {
            return $source;
        }
    }

    /**
     * Allows binding to a data source driver.
     *
     * @access  public
     * @param   mixed   $source     The data source driver object
     * @return  mixed               True if successful, otherwise PEAR_Error
     */
    function bindDataSource(&$source)
    {
        if (is_subclass_of($source, 'structures_datagrid_datasource_common')) {
            $this->_dataSource =& $source;
            if (PEAR::isError($result = $this->fetchDataSource())) {
                unset ($this->_dataSource);
                return $result;
            }
            if ($columnSet = $this->_dataSource->getColumns()) {
                $this->columnSet = array_merge($this->columnSet, $columnSet);
            }
            if (isset ($this->renderer)) {
                $this->renderer->setData($this->columnSet, $this->recordSet);
                $this->renderer->setLimit($this->page, $this->rowLimit, 
                                          $this->getRecordCount());
            }
        } else {
            return new PEAR_Error('Invalid data source type, ' . 
                                  'must be a valid data source driver class');
        }
        
        return true;
    }

    function fetchDataSource()
    {
        if ($this->_dataSource != null) {
            // Determine Page
            $page = $this->page ? $this->page - 1 : 0;
            
            // Fetch the Data
            $recordSet = $this->_dataSource->fetch(
                            ($page*$this->rowLimit),
                            $this->rowLimit, $this->sortArray[0],
                            $this->sortArray[1]);
                            
            if (PEAR::isError($recordSet)) {
                return $recordSet;
            } else {
                $this->recordSet = array_merge($this->recordSet, $recordSet);
                /*
                if (count($columnSet = $this->_dataSource->getColumns())) {
                    $this->columnSet = $columnSet;
                }
                */
            }
        }
    }
    
    
    /**
     * Adds a DataGrid_Record object to this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Record   $record     The record
     *          object to add. This object must be a Structures_DataGrid_Record
     *          object.
     * @return  bool            True if successful, otherwise false.
     */
    function addRecord($record)
    {
        if (is_a($record, 'structures_datagrid_record')) {
            $this->recordSet = array_merge($this->recordSet,
                                           array($record->getRecord()));
            return true;
        } else {
            return new PEAR_Error('Not a valid DataGrid Record');
        }
    }

    /**
     * Drops a DataGrid_Record object from this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Record   $record     The record
     *          object to drop. This object must be a Structures_DataGrid_Record
     *          object.
     * @return void
     */
    function dropRecord($record)
    {
        unset($this->recordSet[$record->getRecord()]);
    }

    /**
     * Sorts the records by the defined field.
     * Do not use this method if data is coming from a database as sorting
     * is much faster coming directly from the database itself.
     *
     * @access  public
     * @param   string $sortBy      The field to sort the record set by.
     * @param   string $direction   The sort direction, either ASC or DESC.
     * @return  void
     */
    function sortRecordSet($sortBy, $direction = 'ASC')
    {
        $this->sortArray = array($sortBy, $direction);
        if ($this->_dataSource) {
            $this->_dataSource->sort($sortBy, $direction);
        } else {
            usort($this->recordSet, array($this, '_sort'));
        }
    }
    
    function _sort($a, $b, $i = 0)
    {
        //$bool = strnatcmp($a[$this->sortArray[0]], $b[$this->sortArray[0]]);
        $bool = strnatcasecmp($a[$this->sortArray[0]], $b[$this->sortArray[0]]);
        
        if ($this->sortArray[1] == 'DESC') {
            $bool = $bool * -1;
        }
        
        return $bool;
    }
    
    /**
     * Parse HTTP Request parameters
     *
     * @access  private
     * @return  array      Associative array of parsed arguments, each of these 
     *                     defaulting to null if not found. 
     */
    function _parseHttpRequest()
    {
        $prefix = $this->_requestPrefix;
        
        // Determine page, sort and direction values
        
        if (!$this->_forcePage) {
            if (isset($_REQUEST[$prefix . 'page'])) {
                // Use POST, GET, or COOKIE value in respective order
                if (isset($_POST[$prefix . 'page'])) {
                    $this->page = $_POST[$prefix . 'page'];
                } elseif (isset($_GET[$prefix . 'page'])) {
                    $this->page = $_GET[$prefix . 'page'];
                } elseif (isset($_COOKIE[$prefix . 'page'])) {
                    $this->page = $_COOKIE[$prefix . 'page'];
                } 
            } else {
                $this->page = 1;
            }
            if (!is_numeric ($this->page)) {
                $this->page = 1;
            }
        } 
        
        if (isset($_REQUEST[$prefix . 'orderBy'])) {
            // Use POST, GET, or COOKIE value in respective order
            if (isset($_POST[$prefix . 'orderBy'])) {
                $this->sortArray[0] = $_POST[$prefix . 'orderBy'];
            } elseif (isset($_GET[$prefix . 'orderBy'])) {
                $this->sortArray[0] = $_GET[$prefix . 'orderBy'];
            } elseif (isset($_COOKIE[$prefix . 'orderBy'])) {
                $this->sortArray[0] = $_COOKIE[$prefix . 'orderBy'];
            }
        }
        
        if (isset($_REQUEST[$prefix . 'direction'])) {
            // Use POST, GET, or COOKIE value in respective order
            if (isset($_POST[$prefix . 'direction'])) {
                $this->sortArray[1] = $_POST[$prefix . 'direction'];
            } elseif (isset($_GET[$prefix . 'direction'])) {
                $this->sortArray[1] = $_GET[$prefix . 'direction'];
            } elseif (isset($_COOKIE[$prefix . 'direction'])) {
                $this->sortArray[1] = $_COOKIE[$prefix . 'direction'];
            }
        }
    }     

    /**
     * Detect source type
     *
     * @param   mixed   $source     Some kind of source
     * @param   array   $options    Options passed to dataSourceFactory()
     * @return  string              The type constant of this source or null if
     *                              it couldn't be detected
     * @access  private
     * @todo    Add CSV detector.  Possible rewrite in IFs to allow for
     *          hierarchy for seperating file handle sources from others
     */
    function _detectSourceType($source, $options = array())
    {
        switch(true) {
            // DB_DataObject
            case (is_subclass_of($source, 'db_dataobject')):
                return DATAGRID_SOURCE_DATAOBJECT;
                break;

            // DB_Result
            case (strtolower(get_class($source)) == 'db_result'):
                return DATAGRID_SOURCE_DB;
                break;
                
            // Array
            case (is_array($source)):
                return DATAGRID_SOURCE_ARRAY;
                break;

            // RSS
            case (is_string($source) && stristr('<rss', $source)):
            case (is_string($source) and stristr('<rdf:RDF', $source)):
                return DATAGRID_SOURCE_RSS;
                break;

            // XML
            case (is_string($source) and ereg('^ *<\?xml', $source)):
                return DATAGRID_SOURCE_XML;
                break;
            
            // DBQuery / MDB2
            case (is_string($source) 
                  and preg_match('#SELECT\s.*\sFROM#is', $source) === 1):
                if (   array_key_exists('backend', $options)
                    && $options['backend'] == 'MDB2'
                ) {
                    return DATAGRID_SOURCE_MDB2;
                }
                return DATAGRID_SOURCE_DBQUERY; 
                break;

            // DBTable
            case (strtolower(get_parent_class($source)) == 'db_table'):
                return DATAGRID_SOURCE_DBTABLE;
                break;

            // CSV
            //case (is_string($source)):
            //    return DATAGRID_SOURCE_CSV;
            //    break;
                
            default:
                return null;
                break;
        }
    }

    /*
    function _detectRendererType($container)
    {
        if (is_subclass_of($container, 'html_table')) {
            return DATAGRID_RENDER_TABLE;
        } // FIXME: and so on...

        return null;
    }
    */

    function build()
    {
        if (isset($this->_dataSource)) {
            $this->renderer->build();
            $this->_isBuilt = true;
        }
    }
}

?>
