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

require_once 'HTML/Table.php';

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
     * GET parameters prefix
     * @var string
     */
     var $requestPrefix;

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
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_HTMLTable()
    {
        $this->_table = new HTML_Table();
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
        $this->_table->setRowAttributes(0, $attribs, true);
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
        $this->table->setAutoFill($value);
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
     * If you need to change the request variables, you can define a prefix.
     * This is extra useful when using multiple datagrids.
     *
     * @access  public
     * @param   string $prefix      The prefix to use on request variables;
     */
    function setRequestPrefix($prefix)
    {
        $this->requestPrefix = $prefix;
    }
    
    
    /**
     * Prints the HTML for the DataGrid
     *
     * @access  public
     */
    function render(&$dg)
    {
        echo $this->toHTML($dg);
    }

    /**
     * Generates the HTML for the DataGrid
     *
     * @access  public
     * @param   object Structures_DataGrid  $dg     The DataGrid to render
     * @return  string      The HTML of the DataGrid
     */
    function toHTML(&$dg)
    {
        $table =& $this->getTable($dg);

        return $table->toHTML();
    } 
    
    /**
     * Gets the HTML_Table object for the DataGrid
     *
     * @access  public
     * @param   object Structures_DataGrid  $dg     The DataGrid to render
     * @return  object HTML_Table   The HTML Table object for the DataGrid
     */
    function &getTable(&$dg)
    {
        $this->_dg = &$dg;

        if (!$this->_rendered) {
            // Get the data to be rendered
            $dg->fetchDataSource();

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
            $this->_table->altRowAttributes(1,
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
        $cnt = 0;
        foreach ($this->_dg->columnSet as $column) {
            //Define Content
            if ($column->orderBy != null) {
                // Determine Direction
                if ($this->_dg->sortArray[0] == $column->orderBy && 
                    $this->_dg->sortArray[1] == 'ASC') {
                    $direction = $this->requestPrefix . 'direction=DESC';
                } else {
                    $direction = $this->requestPrefix . 'direction=ASC';
                }

                // Build URL -- This needs much refinement :)
                if (isset($this->path)) {
                    $url = $this->path . '?';
                } else {
                    $url = $_SERVER['PHP_SELF'] . '?';
                }
                if (isset($_SERVER['QUERY_STRING'])) {
                    $qString = explode('&', $_SERVER['QUERY_STRING']);
                    $i = 0;
                    foreach($qString as $element) {
                        if ($element != '') {
                            if (stristr($element, $this->requestPrefix . 'orderBy')) {
                                $url .= $this->requestPrefix . 'orderBy=' .
                                        $column->orderBy;
                                $orderByExists = true;
                            } elseif (stristr($element, $this->requestPrefix . 'direction')) {
                                $url .= $direction;
                            } elseif (stristr($element, $this->requestPrefix . 'page') && 
                                      $this->sortingResetsPaging) {
                                $url .= $this->requestPrefix . 'page=1';
                            } else {
                                $url .= $element;
                            }
                        }
                        $i++;
                        if ($i < count($qString)) {
                            $url .= '&amp;';
                        }
                    }

                    if (!isset($orderByExists)) {
                        if ($qString[0] != '') {
                            $url .= '&amp;' . $this->requestPrefix . 'orderBy=' . 
                                    $column->orderBy . '&amp;' . $direction;
                        } else {
                            $url .= $this->requestPrefix . 'orderBy=' . 
                                    $column->orderBy . '&amp;' . $direction;
                        }
                    }
                } else {
                    $url .= $this->requestPrefix . 'orderBy=' . 
                            $column->orderBy . '&amp;' . $direction;
                }

                $iconVar = "sortIcon" . 
                           ($this->_dg->sortArray[1] ? $this->_dg->sortArray[1] : 'ASC');
                $str = '<a href="' . $url . '">' . $column->columnName;
                if (($this->$iconVar != '') && 
                    ($this->_dg->sortArray[0] == $column->orderBy)) {
                    $str .= ' ' . $this->$iconVar;
                }
                $str .= '</a>';
                
            } else {
                $str = $column->columnName;
            }

            // Print Content to HTML_Table
            $this->_table->setHeaderContents(0, $cnt, $str);
            $this->_table->setCellAttributes(0, $cnt, $column->attribs);

            $cnt++;
        }

        // Define Table Header attributes
        //$attr = array('bgcolor' => $this->headerBgColor);
        //$this->_table->setRowAttributes(0, $attr);
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

            // Determine looping values
            /*
            if ($this->_dg->page > 1) {
                $begin = ($this->_dg->page - 1) * $this->_dg->rowLimit;
                $limit = $this->_dg->page * $this->_dg->rowLimit;
            } else {
                $begin = 0;
                if ($this->_dg->rowLimit == null) {
                    $limit = count($this->_dg->recordSet);
                } else {
                    $limit = $this->_dg->rowLimit;
                }
            }
            */

            // Begin loop
            //for ($i = $begin; $i < $limit; $i++) {
            for ($i = 0; $i < $this->_dg->rowLimit; $i++) {
                if (isset($this->_dg->recordSet[$i])) {
                    // Print Row
                    $cnt = 0;
                    $row = $this->_dg->recordSet[$i];
                    foreach ($this->_dg->columnSet as $column) {
                        $rowCnt = $i+1;

                        // Build Content
                        if (isset($column->formatter)) {
                            $content = $column->formatter($row);
                        } elseif (!isset($column->fieldName)) {
                            if ($column->autoFill != '') {
                                $content = $column->autoFill;
                            } else {
                                $content = $column->columnName;
                            }
                        } else {
                            $content = $row[$column->fieldName];
                        }

                        // Print Content to HTML_Table
                        $this->_table->setCellContents($rowCnt, $cnt, $content);
                        $this->_table->setCellAttributes($rowCnt, $cnt,
                                                         $column->attribs);

                        $cnt++;
                    }
                } else {
                    // Determine if empty row should be printed
                    if ($this->allowEmptyRows) {
                        $rowCnt++;
                        for ($j=0; $j<count($this->_dg->columnSet); $j++) {
                            $this->_table->setCellAttributes($rowCnt, $j,
                                                     $this->emptyRowAttributes);
                            $this->_table->setCellContents($rowCnt, $j,
                                                           '&nbsp;');
                        }
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
                          'perPage' => $this->_dg->rowLimit,
                          'urlVar' => $this->requestPrefix . 'page');
        $options = array_merge($defaults, $options);
        $this->pager =& Pager::factory($options);
    }    

}

?>