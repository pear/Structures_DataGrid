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
define('DATAGRID_RENDER_TABLE',    'HTMLTable');
define('DATAGRID_RENDER_SMARTY',   'Smarty');
define('DATAGRID_RENDER_XML',      'XML');
define('DATAGRID_RENDER_XLS',      'XLS');
define('DATAGRID_RENDER_XUL',      'XUL');
define('DATAGRID_RENDER_CSV',      'CSV');
define('DATAGRID_RENDER_CONSOLE',  'Console');

define('DATAGRID_RENDER_DEFAULT',  DATAGRID_RENDER_TABLE);

// PEAR_Error code for unsupported features
define('DATAGRID_ERROR_UNSUPPORTED', 1);

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
    var $_renderer;

    /**
     * Renderer driver type
     * @var object Structures_DataGrid_Renderer_* family
     * @access private
     */ 
    var $_rendererType = null;

    /**
     * Array of columns.  Columns are defined as a DataGridColumn object.
     * @var array
     * @access private
     */
    var $columnSet = array();

    /**
     * Array of records.  
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
     * Fields/directions to sort the data by
     *
     * @var array Form: array (fieldName => direction, ....)
     * @access private
     */
    var $sortSpec = array();

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
                                 $rendererType = null)
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

        if (!is_null ($rendererType)) {
            $this->setRenderer($rendererType);
        }
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
        $fp = @fopen($file, 'r', true);
        if (is_resource($fp)) {
            @fclose($fp);
            return true;
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
                $error = PEAR::raiseError($msg);
                return $error;
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
            $error = PEAR::raiseError('Unable to determine the data source type. '.
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

        if ($this->sortSpec) {
            $driver->setCurrentSorting($this->sortSpec);
        }

        return $driver;
    }

    /**
     * Render the datagrid
     *
     * @param  string   $limit  The row limit per page.
     * @param  string   $page   The current page viewed.
     * @access public
     * @return mixed    True or PEAR_Error
     */
    function render()
    {
        isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        
        $this->_renderer->isBuilt() or $this->build();
        $test = $this->_renderer->render();

        if (PEAR::isError($test)) {
            if ($test->getCode() == DATAGRID_ERROR_UNSUPPORTED) {
                $type = is_null($this->_rendererType) 
                        ? get_class($this->_renderer)
                        : $this->_rendererType;
                return PEAR::raiseError("The $type driver does not support the ".
                                      "render() method. Try using fill().");
            } else {
                return $test;
            }
        }
    }

    /**
     * Return the datagrid output
     *
     * @access public
     * @return mixed The datagrid output (Usually a string : HTML, CSV, etc...)
     *               or a PEAR_Error
     */
    function getOutput()
    {
        isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        $this->_renderer->isBuilt() or $this->build();
        return $this->_renderer->getOutput();
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
        isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        return $this->_renderer;
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
            $this->_rendererType = $type;
            return $this->attachRenderer($renderer);
        } else {
            return $renderer;
        }
    }

    /**
     * Attach a rendering driver
     * 
     * @param object $renderer Driver object, subclassing 
     *                         Structures_DataGrid_Renderer
     * @return mixed           Either true or a PEAR_Error object
     * @access public
     */
    function attachRenderer(&$renderer)
    {
        if (is_subclass_of($renderer, 'structures_datagrid_renderer')) {
            $this->_renderer =& $renderer;
            if (isset ($this->_dataSource)) {
                $this->_renderer->setData($this->columnSet, $this->recordSet);
                $this->_renderer->setLimit($this->page, $this->rowLimit, 
                                          $this->getRecordCount());
            }
        } else {
            return PEAR::raiseError('Invalid renderer type, ' . 
                                  'must be a valid renderer driver class');
        }

        return true;
    }

    /**
     * Fill a rendering container with data
     * 
     * @param object $container A rendering container of any of the supported
     *                          types (example : an HTML_Table object, 
     *                          a Spreadsheet_Excel_Writer object, etc...)
     * @param array  $options   Options for the corresponding rendering driver
     * @param string $type      Explicit type in case the container type 
     *                          can't be detected
     * @return mixed            Either true or a PEAR_Error object 
     * @access public
     */
    function fill(&$container, $options = array(), $type = null)
    {
        if (is_null($type)) {
            $type = $this->_detectRendererType($container);
            if (is_null($type)) {
                return PEAR::raiseError('The rendering container type can not '.
                                      'be automatically detected. Please ' . 
                                      'specify its type explicitly.');
            }
        }

        /* Is a renderer driver already loaded and does it exactly match 
         * the driver class name that corresponds to $type ? */
        if (!isset ($this->_renderer) 
            or !is_a($this->_renderer, "Structures_DataGrid_Renderer_$type")) {
            /* No, then load the right driver */
            if (PEAR::isError($test = $this->setRenderer($type, $options))) {
                return $test;
            }
        }

        $test = $this->_renderer->setContainer($container);
        if (PEAR::isError($test)) {
            if ($test->getCode() == DATAGRID_ERROR_UNSUPPORTED) {
                return PEAR::raiseError("The $type driver does not support the " . 
                                      "fill() method. Try using render().");
            } else {
                return $test;
            }
        }

        $this->_renderer->build();

        return true;
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
        if ((!count($this->columnSet)) && (count($this->recordSet))) {
            $arrayKeys = array_keys($this->recordSet[0]);
            foreach ($arrayKeys as $key) {
                $column = new Structures_DataGrid_Column($key, $key, $key);
                $this->addColumn($column);
            }
        }
    }

    /**
     * Retrieves the current page number when paging is implemented
     *
     * @return int       the current page number
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
     * @param  mixed     $page       The current page number (as string or int).
     */
    function setCurrentPage($page)
    {
        $this->page = $page;
    }

    /**
     * Returns the total number of pages
     * (returns 0 if there are no records, returns 1 if there is no row limit)
     *
     * @return int       the total number of pages
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
     * Returns the number of columns
     *
     * @return int       the number of records
     * @access public
     */
    function getColumnCount()
    {
        return count($this->columnSet);
    }

    /**
     * Returns the total number of records
     *
     * @return int       the total number of records
     * @access public
     */
    function getRecordCount()
    {
        if (isset($this->_dataSource)) {
            return $this->_dataSource->count();
        } else {
            return count($this->recordSet);
        }
    }    

    /**
     * Returns the number of the first record of the current page
     * (returns 0 if there are no records, returns 1 if there is no row limit)
     *
     * @return int       the number of the first record currently shown
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
     * @return int       the number of the last record currently shown
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
        $this->_parseHttpRequest();

        if (isset($this->_renderer)) {

            $this->_renderer->setRequestPrefix($prefix);

            /* We just called parseHttpRequest() using a new requestPrefix.
             * The page and sort request might have changed, so we need
             * to pass them again to the renderer */
            $this->_renderer->setLimit($this->page, $this->rowLimit, 
                                      $this->getRecordCount());
            if ($this->sortSpec) {
                $this->_renderer->setCurrentSorting($this->sortSpec);
            }
        }
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
     * Returns a reference to a DataGrid_Column object
     *
     * @access  public
     * @param   string   $name     The name of the column to be returned.
     * @return  object   Either the column object or a PEAR_Error if there is
     *          no such column.
     */
    function &getColumnByName($name)
    {
        foreach ($this->columnSet as $key => $column) {
            if ($column->columnName === $name) {
                return $this->columnSet[$key];
            }
        }
        $error = PEAR::raiseError("Column '$name' does not exist");
        return $error;
    }

    /**
     * A simple way to add a record set to the datagrid
     *
     * @access  public
     * @param   mixed   $container  The record set in any of the supported data
     *                              source types
     * @param   array   $options    Optional. The options to be used for the
     *                              data source
     * @param   string  $type       Optional. The data source type
     * @return  bool                True if successful, otherwise PEAR_Error.
     */
    function bind($container, $options = array(), $type = null)
    {
        $source =& Structures_DataGrid::datasourceFactory($container, $options, $type);
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
        if (is_subclass_of($source, 'structures_datagrid_datasource')) {
            $this->_dataSource =& $source;
            if (PEAR::isError($result = $this->fetchDataSource())) {
                unset ($this->_dataSource);
                return $result;
            }
            if ($columnSet = $this->_dataSource->getColumns()) {
                $this->columnSet = array_merge($this->columnSet, $columnSet);
            }
            if (isset ($this->_renderer)) {
                $this->_renderer->setData($this->columnSet, $this->recordSet);
                $this->_renderer->setLimit($this->page, $this->rowLimit, 
                                          $this->getRecordCount());
            }
        } else {
            return PEAR::raiseError('Invalid data source type, ' . 
                                  'must be a valid data source driver class');
        }

        return true;
    }

    /**
     * Fetch data from the datasource 
     *
     * @return mixed Either true or a PEAR_Error object
     * @access private
     */
    function fetchDataSource()
    {
        if (isset($this->_dataSource)) {
            // Determine Page
            $page = $this->page ? $this->page - 1 : 0;

            // Fetch the Data
            reset($this->sortSpec);
            if (list($field,$direction) = each($this->sortSpec)) {
                $recordSet = $this->_dataSource->fetch(
                                ($page * $this->rowLimit),
                                $this->rowLimit,
                                $field,
                                $direction);
            } else {
                $recordSet = $this->_dataSource->fetch(
                                ($page * $this->rowLimit),
                                $this->rowLimit);
            }

            if (PEAR::isError($recordSet)) {
                return $recordSet;
            } else {
                $this->recordSet = array_merge($this->recordSet, $recordSet);
                return true;
            }
        } else {
            return PEAR::raiseError("Cannot fetch data: no datasource driver loaded.");
        }
    }

    /**
     * Adds a DataGrid_Record object to this DataGrid object
     *
     * FIXME: Isn't this obsolete ?
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
            return PEAR::raiseError('Not a valid DataGrid Record');
        }
    }

    /**
     * Drops a DataGrid_Record object from this DataGrid object
     *
     * FIXME: Isn't this obsolete ?
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
        $this->sortSpec = array($sortBy => $direction);
        if (isset($this->_dataSource)) {
            $this->_dataSource->sort($sortBy, $direction);
        } else {
            $this->_sortCallbackField = $sortBy;
            $this->_sortCallbackDirection = $direction;
            usort($this->recordSet, array($this, '_sort'));
        }
    }

    /**
     * Callback method used in sortRecordSet()
     *
     * @access  private
     * @param   string $a           First value
     * @param   string $direction   Second value
     * @return  boolean
     */
    function _sort($a, $b)
    {
        $bool = strnatcasecmp($a[$this->_sortCallbackField], $b[$this->_sortCallbackField]);
        
        if ($this->_sortCallbackDirection == 'DESC') {
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

        $orderBy = '';
        if (isset($_REQUEST[$prefix . 'orderBy'])) {
            // Use POST, GET, or COOKIE value in respective order
            if (isset($_POST[$prefix . 'orderBy'])) {
                $orderBy = $_POST[$prefix . 'orderBy'];
            } elseif (isset($_GET[$prefix . 'orderBy'])) {
                $orderBy = $_GET[$prefix . 'orderBy'];
            } elseif (isset($_COOKIE[$prefix . 'orderBy'])) {
                $orderBy = $_COOKIE[$prefix . 'orderBy'];
            }
        }

        $direction = 'ASC';
        if (isset($_REQUEST[$prefix . 'direction'])) {
            // Use POST, GET, or COOKIE value in respective order
            if (isset($_POST[$prefix . 'direction'])) {
                $direction = $_POST[$prefix . 'direction'];
            } elseif (isset($_GET[$prefix . 'direction'])) {
                $direction = $_GET[$prefix . 'direction'];
            } elseif (isset($_COOKIE[$prefix . 'direction'])) {
                $direction = $_COOKIE[$prefix . 'direction'];
            }
        }

        if ($orderBy) {
            $this->sortSpec[$orderBy] = $direction;
        }
    }     

    /**
     * Detect datasource container type
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
            // FIXME: should use is_subclass_of() 
            case (strtolower(get_parent_class($source)) == 'db_dataobject'):
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
            case (   is_string($source) 
                  && preg_match('#SELECT\s.*\sFROM#is', $source) === 1
                 ):
                if ((     array_key_exists('connection', $options)
                       && is_subclass_of($options['connection'],
                                         'mdb2_driver_common')
                    ) || (
                          array_key_exists('backend', $options)
                       && $options['backend'] == 'MDB2'
                )) {
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

    /**
     * Detect rendering container type
     * 
     * @param object $container The rendering container
     * @return string           The container type or null if unrecognized
     * @access private
     */
    function _detectRendererType(&$container)
    {
        if (is_a($container, 'html_table') or is_subclass_of($container, 'html_table')) {
            return DATAGRID_RENDER_TABLE;
        } else if (is_a($container, 'smarty') or is_subclass_of($container, 'smarty')) {
            return DATAGRID_RENDER_SMARTY;
        } else if (is_a($container, 'spreadsheet_excel_writer_workbook') 
                   or is_subclass_of($container, 
                                     'spreadsheet_excel_writer_workbook')) {
            return DATAGRID_RENDER_XLS;
        } // FIXME: and so on...

        return null;
    }

    /**
     * Build the datagrid
     * 
     * @return mixed Either true or a PEAR_Error object
     * @access public
     */
    function build()
    {
        $this->_setDefaultHeaders();
        if (isset($this->_dataSource)) {
            isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
            $this->_renderer->build();
            return true;
        } else {
            return PEAR::raiseError("Cannot build the datagrid: no datasource driver loaded");
        }
    }

    /**
     * Provide some BC fix (require PHP5)
     * 
     * This is a PHP5 magic method used to simulate the old public 
     * $renderer property
     */
    function __get($var)
    {
        if ($var == 'renderer') {
            isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
            return $this->_renderer;
        }
    }

    /**
     * Set a single renderer option
     *
     * @param   string  $name       Option name
     * @param   mixed   $value      Option value
     * @access  public
     */
    function setRendererOption($name,$value)
    {
        $this->setRendererOptions(array($name => $value));
    }

    /**
     * Set multiple renderer options
     *
     * @param   array   $options    An associative array of the form :
     *                              array("option_name" => "option_value",...)
     * @access  public
     */
    function setRendererOptions($options)
    {
        isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        $this->_renderer->setOptions($options);
    }

    /**
     * Set a single datasource option
     *
     * @param   string  $name       Option name
     * @param   mixed   $value      Option value
     * @access  public
     */
    function setDataSourceOption($name,$value)
    {
        return $this->setDataSourceOptions(array($name => $value));
    }

    /**
     * Set multiple datasource options
     *
     * @param   array   $options    An associative array of the form :
     *                              array("option_name" => "option_value",...)
     * @access  public
     */
    function setDataSourceOptions($options)
    {
        if (isset($this->_dataSource)) {
            $this->_dataSource->setOptions($options);
        } else {
            return PEAR::raiseError('Unable to set options ; No datasource loaded.');
        }
    }

    /**
     * Return paging links
     *
     * This method will return paging links if the renderer driver supports
     * generating them.
     *
     * Useful options (See Pager's documentation for more) :
     * mode      : The mode of pager to use
     * separator : The string to use to separate each page link
     * prevImg   : The string for the previous page link
     * nextImg   : The string for the forward page link
     * delta     : The number of pages to display before and
     *             after the current page
     * 
     * Notice : Some drivers may not use HTML::Pager. In a such case, the above
     * options may not be supported. See the driver-specific documentation for 
     * more.
     *             
     * @param  array $options Paging options as supported by the driver 
     * @return mixed          Links as a string, array,... (driver-specific)
     *                        or a PEAR_Error if not implemented
     * @access public
     */
    function getPaging($options = array())
    {
        isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        return $this->_renderer->getPaging($options);
    }
}

?>
