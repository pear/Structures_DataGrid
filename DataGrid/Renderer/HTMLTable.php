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
// | Author: Andrew Nagy <asnagy@webitecture.org>                         |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'HTML/Table.php';
require_once 'PHP/Compat/Function/http_build_query.php';

/**
 * Structures_DataGrid_Renderer_HTMLTable Class
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_HTMLTable
{
    /**
     * Use the table header
     * @var bool
     */
    var $header = true;

    /**
     * An associative array containing each attribute of the even rows
     * @var array
     */
    var $evenRowAttributes;

    /**
     * An associative array containing each attribute of the odd rows
     * @var array
     */
    var $oddRowAttributes;

    /**
     * A boolean value to determine if empty rows should be printed.
     * @var bool
     */
    var $allowEmptyRows;

    /**
     * An associative array containing the attributes for empty rows
     * @var array
     */
    var $emptyRowAttributes = array();

    /**
     * Wether or not to reset paging on sorting request
     * @var bool
     */
    var $sortingResetsPaging = true;    
    
    /**
     * The complete path for the sorting links.  If not defined, PHP_SELF is
     * used.
     * @var string
     */
    var $path;

    /**
     * The icon to define that sorting is currently Ascending.  Can be text or
     * HTML to define an image.
     * @var string
     */
     var $sortIconASC;

    /**
     * The icon to define that sorting is currently Descending.  Can be text or
     * HTML to define an image.
     * @var string
     */
     var $sortIconDESC;
          
     
    /**
     * The HTML::Pager object that controls paging logic.
     * @var object Pager
     */
    var $pager;
         
    /**
     * The structures_datagrid object
     * @var object Structures_DataGrid
     */
    var $_dg;

    /**
     * The html_table object
     * @var object HTML_Table
     */
    var $_table;

    /**
     * A switch to determine the state of the table
     * @var bool
     */
    var $_rendered = false;

    /**
     * Variables to be added to the generated links
     * @var array
     */
    var $_extraVars = array();

    /**
     * Variables to be removed from the generated links
     * @var array
     */
    var $_excludeVars = array();
       
    /**
     * Wether to automagically right-align numeric values or not
     * @var bool
     */
    var $_autoAlign = true;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access  public
     */
    function Structures_DataGrid_Renderer_HTMLTable(&$dg)
    {
        $this->_dg =& $dg;
        $this->_table = new HTML_Table(null, null, true);
        $this->_tableHeader =& $this->_table->getHeader();
        $this->_tableBody =& $this->_table->getBody();
    }

    /**
     * Set a table attribute
     *
     * @access public
     * @param  string   $name    The CSS class to use for the table.
     */
    function setTableAttribute($attr, $value)
    {
        $this->_table->_attributes[$attr] = $value;
    }

    /**
     * Define the table's header row attrbiutes
     *
     * @access public
     * @param  array     $attribs   The attributes for the table header row.
     */
    function setTableHeaderAttributes($attribs)
    {
        $this->_tableHeader->setRowAttributes(0, $attribs, true);
    }

    /**
     * Define the table's odd row attributes
     *
     * @access public
     * @param  array    $attribs    The associative array of attributes for the
     *                              odd table row.
     * @see HTML_Table::setCellAttributes
     */
    function setTableOddRowAttributes($attribs)
    {
        $this->oddRowAttributes = $attribs;
    }

    /**
     * Define the table's even row attrbiutes
     *
     * @access public
     * @param  array    $attribs    The associative array of attributes for the
     *                              even table row.
     * @see HTML_Table::setCellAttributes
     */
    function setTableEvenRowAttributes($attribs)
    {
        $this->evenRowAttributes = $attribs;
    }

    /**
     * Define the table's autofill value.  This value appears only in an empty
     * table cell.
     *
     * @access public
     * @param  string    $value     The value to use for empty cells.
     */
    function setAutoFill($value)
    {
        $this->_tableBody->setAutoFill($value);
    }

    /**
     * Set wether to automatically right-align numeric values or not
     *
     * This is enabled by default.
     *
     * @access public
     * @param  bool   $state   Auto-alignment state
     */
    function setAutoAlign ($state)
    {
        $this->_autoAlign = $state;
    }
    
    /**
     * In order for the DataGrid to render "Empty Rows" to allow for uniformity
     * across pages with varying results, set this option to true.  An example
     * of this would be when you have 11 results and have the DataGrid show 10 
     * records per page. The last page will only show one row in the table, 
     * unless this option is turned on in which it will render 10 rows, 9 of 
     * which will be empty.
     *
     * @access public
     * @param  bool      $value          A boolean value to determine whether or
     *                                   not to display the empty rows.
     * @param  array     $attributes     The empty row attributes defined in an 
     *                                   array.
     */
    function allowEmptyRows($value, $attributes = array())
    {      
        if ($value) {
            $this->allowEmptyRows = true;
        } else {
            $this->allowEmptyRows = false;
        }
 
        $this->emptyRowAttributes = $attributes;
    }

    /**
     * Determines whether or not to use the Header
     *
     * @access  public
     * @param   bool    $bool   value to determine to use the header or not.
     */
    function useHeader($bool)
    {
        $this->header = (bool)$bool;
    }
    
    /**
     * Add custom GET variables to the generated links
     *
     * This method adds the provided variables to the paging and sorting
     * links. The variable values are automatically url encoded.
     * 
     * @param   array   $vars   Array of the form (key => value, ...) 
     * @access  public
     * @return  void
     */
    function setExtraVars($vars)
    {
        $this->_extraVars = $vars;
    }
    
    /**
     * Exclude GET variables from the generated links
     *
     * This method excludes the provided variables from the paging and sorting
     * links. This is helpful when using variables that determine what page to
     * show such as an 'action' variable, etc.
     * 
     * @param   array       $vars       An array of variables to remove
     * @access  public
     * @return  void
     */
    function excludeVars($vars)
    {
        $this->_excludeVars = array_merge($this->_excludeVars, $vars);
    }    
    
    /**
     * Prints the HTML for the DataGrid
     *
     * @access  public
     */
    function render()
    {
        echo $this->toHTML();
    }

    /**
     * Generates the HTML for the DataGrid
     *
     * @access  public
     * @return  string      The HTML of the DataGrid
     */
    function toHTML()
    {
        $table =& $this->getTable();

        return $table->toHTML();
    } 
    
    /**
     * Gets the HTML_Table object for the DataGrid
     *
     * @access  public
     * @return  object HTML_Table   The HTML Table object for the DataGrid
     */
    function &getTable()
    {
        $dg =& $this->_dg;

        if (!$this->_rendered) {
            /*
            // Get the data to be rendered
            if (PEAR::isError($result = $dg->fetchDataSource())) {
                return $result;
            }
            */
            // Check to see if column headers exist, if not create them
            // This must follow after any fetch method call
            $dg->_setDefaultHeaders();
                        
            // Define Table Header
            if ($this->header) {
                $this->_buildHTMLTableHeader();
            }
    
            // Build Table Data
            $this->_buildHTMLTableBody();
    
            // Define Alternating Row attributes
            $this->_tableBody->altRowAttributes(0,
                                            $this->evenRowAttributes,
                                            $this->oddRowAttributes,
                                            TRUE);
                                            
            $this->_rendered = true;
        }
        
        return $this->_table;
    }   

    /**
     * Sets the rendered status.  This can be used to "flush the cache" in case
     * you need to render the datagrid twice with the second time having changes
     *
     * @access  public
     * @params  bool        $status     The rendered status of the DataGrid
     */
    function setRendered($status)
    {
        $this->_rendered = (bool)$status;
    }   
        
    /**
     * Handles building the header of the DataGrid
     *
     * @access  private
     * @return  void
     * @todo    Redesign/Rework the header URL building.
     */
    function _buildHTMLTableHeader()
    {
        $prefix = $this->_dg->_requestPrefix;
       
        $cnt = 0;
       
        // Build the list of common get parameters
        $common   = $this->_extraVars;
        $ignore   = $this->_excludeVars;
        $ignore[] = $prefix . 'orderBy';
        $ignore[] = $prefix . 'page';
        $ignore[] = $prefix . 'direction';
        foreach ($_GET as $key => $val) {
            if (!in_array ($key, $ignore)) {
                $common[$key] = $val;
            }
        }

        foreach ($this->_dg->columnSet as $column) {
            //Define Content
            if (!is_null ($column->orderBy)) {
                // Determine Direction
                if ($this->_dg->sortArray[0] == $column->orderBy && 
                    $this->_dg->sortArray[1] == 'ASC') {
                    $direction = 'DESC';
                } else {
                    $direction = 'ASC';
                }

                // Build list of GET variables
                $get = array();
                $get[$prefix . 'orderBy'] = $column->orderBy;
                $get[$prefix . 'direction'] = $direction;
                $get[$prefix . 'page'] = 1;

                // Build Link URL
                if (isset($this->path)) {
                    $url = $this->path . '?';
                } else {
                    $url = $_SERVER['PHP_SELF'] . '?';
                }

                // Merge common and column-specific GET variables
                $url .= http_build_query (array_merge ($common, $get));
                
                // Build HTML Link
                $str = '<a href="' . $url . '">' . $column->columnName;
                $iconVar = "sortIcon" . 
                           ($this->_dg->sortArray[1] ? $this->_dg->sortArray[1] : 'ASC');
                if (($this->$iconVar != '') && 
                    ($this->_dg->sortArray[0] == $column->orderBy)) {
                    $str .= ' ' . $this->$iconVar;
                }
                $str .= '</a>';
                
            } else {
                $str = $column->columnName;
            }

            // Print Content to HTML_Table
            $this->_tableHeader->setHeaderContents(0, $cnt, $str);
            $this->_tableHeader->setCellAttributes(0, $cnt, $column->attribs);

            $cnt++;
        }
    }

    /**
     * Handles building the body of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function _buildHTMLTableBody()
    {
        if ($this->_dg->recordSet) {
            if (!isset($this->_dg->rowLimit)) {
                $this->_dg->rowLimit = count($this->_dg->recordSet);
            }
            
            // Determine looping values
            if ($this->_dg->rowLimit >= count($this->_dg->recordSet)) {
                $begin = 0;
                $end = $this->_dg->rowLimit;
            } else {
                if ($this->_dg->page > 1) {
                    $begin = ($this->_dg->page - 1) * $this->_dg->rowLimit;
                    $end = $this->_dg->page * $this->_dg->rowLimit;
                } else {
                    $begin = 0;
                    if ($this->_dg->rowLimit == null) {
                        $end = count($this->_dg->recordSet);
                    } else {
                        $end = $this->_dg->rowLimit;
                    }
                }
            }

            // Begin loop
            $rowCnt = 0;
            for ($i = $begin; $i < $end; $i++) {
                if (isset($this->_dg->recordSet[$i])) {
                    $cnt = 0;
                    $row = $this->_dg->recordSet[$i];
                    foreach ($this->_dg->columnSet as $column) {
                        // Build Content
                        if (isset($column->formatter)) {
                            //Use Formatter                            
                            $content = $column->formatter($row); 
                        } elseif (!isset($column->fieldName)) {
                            if ($column->autoFillValue != '') {
                                // Use AutoFill                                
                                $content = $column->autoFillValue; 
                            } else {
                                // Use Column Name                                
                                $content = $column->columnName;
                            }
                        } else {
                            // Use Record Data
                            $content = htmlspecialchars($row[$column->fieldName]);
                          
                            /* Right-align the content if it is numeric
                               (but don't touch the "align" attributes if it is
                               already set) */
                            if ($this->_autoAlign and is_numeric ($content)
                                and (!isset($column->attribs['align']) 
                                     or empty($column->attribs['align']))) {
                                $column->attribs['align'] = "right";
                            }
                            if (($content == '') && 
                                ($column->autoFillValue != '')) {
                                // Use AutoFill
                                $content = $column->autoFillValue;
                            }
                        }

                        // Set Content in HTML_Table
                        $this->_tableBody->setCellContents($rowCnt, $cnt, $content);
                        $this->_tableBody->setCellAttributes($rowCnt, $cnt,
                                                         $column->attribs);

                        $cnt++;
                    }
                    $rowCnt++;
                } else {
                    // Determine if empty row should be printed
                    if ($this->allowEmptyRows) {
                        for ($j=0; $j<count($this->_dg->columnSet); $j++) {
                            $this->_tableBody->setCellAttributes($rowCnt, $j,
                                                     $this->emptyRowAttributes);
                            $this->_tableBody->setCellContents($rowCnt, $j,
                                                           '&nbsp;');
                        }
                        $rowCnt++;
                    }
                }
            }
        }
    }

    /**
     * Handles the building of the page list for the DataGrid in HTML.
     * This method uses the HTML::Pager class
     *
     * @access  public
     * @param   string $mode        The mode of pager to use
     * @param   string $separator   The string to use to separate each page link
     * @param   string $prev        The string for the previous page link
     * @param   string $next        The string for the forward page link
     * @param   string $delta       The number of pages to display before and
     *                              after the current page
     * @param   array $attrs        Additional attributes for the Pager class
     * @return  string              The HTML for the page links
     * @see     HTML::Pager
     */
    function getPaging($mode = 'Sliding', $separator = '|', $prev = '<<',
                       $next = '>>', $delta = 5, $attrs = null)
    {
        require_once 'Pager/Pager.php';

        // Generate Paging
        $options = array('mode' => $mode,
                         'delta' => $delta,
                         'separator' => $separator,
                         'prevImg' => $prev,
                         'nextImg' => $next);
                         
        if (is_array($attrs)) {
            $options = array_merge($options, $attrs);
        }
        
        if (isset ($options['extraVars'])) {
            $options['extraVars'] = array_merge($options['extraVars'],
                                                $this->_extraVars);
        } else {
            $options['extraVars'] = $this->_extraVars;
        }
        
        if (isset ($options['excludeVars'])) {
            $options['excludeVars'] = array_merge($options['excludeVars'],
                                                  $this->_excludeVars);
        } else {
            $options['excludeVars'] = $this->_excludeVars;
        }
        
        $this->_buildPaging($options);

        // Return paging html
        return $this->pager->links;
    }
    

    /**
     * Handles generating the paging object
     *
     * @param   array        $options        Array of HTML::Pager options
     * @access  private
     * @return  void
     */
    function _buildPaging($options)
    {
        if ($this->_dg->_dataSource != null) {
            $count = $this->_dg->_dataSource->count();
        } else {
            $count = count($this->_dg->recordSet);
        }
        $defaults = array('totalItems' => $count,
                          'perPage' => is_null($this->_dg->rowLimit) ? 
                                            $count : $this->_dg->rowLimit,
                          'urlVar' => $this->_dg->_requestPrefix . 'page',
                          'currentPage' => $this->_dg->page);
        $options = array_merge($defaults, $options);
        $this->pager =& Pager::factory($options);
    }    

}

?>
