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

require_once 'Structures/DataGrid/Renderer/Common.php';
require_once 'HTML/Table.php';
require_once 'PHP/Compat/Function/http_build_query.php';

/**
 * Structures_DataGrid_Renderer_HTMLTable Class
 *
 * Driver for rendering the DataGrid as an HTMLTable
 *
 * Recognized options :
 *
 * - evenRowAttributes  : An associative array containing each attribute of the 
 *                        even rows
 * - oddRowAttributes   : An associative array containing each attribute of the 
 *                        odd rows
 * - emptyRowAttributes : An associative array containing the attributes for 
 *                        empty rows
 * - selfPath           : The complete path for sorting and paging links.  If not 
 *                        defined, PHP_SELF is used.
 * - sortIconASC        : The icon to define that sorting is currently Ascending.  
 *                        Can be text or HTML to define an image.
 * - sortIconDESC       : The icon to define that sorting is currently Descending. 
 *                        Can be text or HTML to define an image.
 * - extraVars          : variables to be added to the generated links
 * - excludeVars        : variables to be removed to the generated links
 * - headersAttributes  : headers cells attributes. This is an array of the form :
 *                        array(fieldName => array(attribute => value, ...) ... )
 *                       
 * 
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_HTMLTable extends Structures_DataGrid_Renderer_Common
{
     
    /**
     * The html_table_storage object for the table header
     * @var object HTML_Table_Storage
     */
    var $_tableHeader;

    /**
     * The html_table_storage object for the table body
     * @var object HTML_Table_Storage
     */
    var $_tableBody;
        
    /**
     * The HTML::Pager object that controls paging logic.
     * @var object Pager
     */
    var $_pager;

    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access  public
     */
    function Structures_DataGrid_Renderer_HTMLTable()
    {
        parent::Structures_DataGrid_Renderer_Common();
        $this->_addDefaultOptions(
            array(
                'evenRowAttributes'  => array(),
                'oddRowAttributes'   => array(),
                'emptyRowAttributes' => array(),
                'selfPath'           => $_SERVER['PHP_SELF'],
                'sortIconASC'        => '',
                'sortIconDESC'       => '',
                'extraVars'          => array(),
                'excludeVars'        => array(),
                'headersAttributes'   => array(),
            )
        );
    }

    
    function init()
    {
        if (is_null($this->_container)) {
            $this->_container = new HTML_Table(null, null, true);
        } 

        $this->_tableHeader =& $this->_container->getHeader();
        $this->_tableBody =& $this->_container->getBody();
    }

    /**
     * Set a table attribute
     *
     * @access public
     * @param  string   $name    The CSS class to use for the table.
     */
    function setTableAttribute($attr, $value)
    {
        if (is_null($this->_container)) {
            $this->init();
        }
        $this->_container->_attributes[$attr] = $value;
    }

    /**
     * Define the table's header row attrbiutes
     *
     * @access public
     * @param  array     $attribs   The attributes for the table header row.
     */
    function setTableHeaderAttributes($attribs)
    {
        if (is_null($this->_container)) {
            $this->init();
        }
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
     * Define the table's even row attributes
     *
     * @access public
     * @param  array    $attribs    The associative array of attributes for the
     *                              even table row.
     * @see HTML_Table::setCellAttributes
     */
    function setTableEvenRowAttributes($attribs)
    {
        $this->_options['evenRowAttributes'] = $attribs;
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
        if (is_null($this->_container)) {
            $this->init();
        }
        $this->_tableBody->setAutoFill($value);
    }

    /**
     * Set whether to automatically right-align numeric values or not
     *
     * This is enabled by default.
     *
     * @access public
     * @param  bool   $state   Auto-alignment state
     */
    function setAutoAlign ($state)
    {
        $this->_options['autoAlign'] = $state;
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
            $this->_options['fillWithEmptyRows'] = true;
        } else {
            $this->_options['fillWithEmptyRows'] = false;
        }
 
        $this->_options['emptyRowAttributes'] = $attributes;
    }

    /**
     * Determines whether or not to use the Header
     *
     * @access  public
     * @param   bool    $bool   value to determine to use the header or not.
     */
    function useHeader($bool)
    {
        $this->_options['buildHeader'] = (bool)$bool;
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
        $this->_options['extraVars'] = $vars;
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
        $this->_options['excludeVars'] = $vars;
    }    
    
    /**
     * Generates the HTML for the DataGrid
     *
     * @access  public
     * @return  string      The HTML of the DataGrid
     */
    function toHTML()
    {
        return $this->getOutput();
    } 
    
    /**
     * Gets the HTML_Table object for the DataGrid
     *
     * OBSOLETE
     * 
     * @access  public
     * @return  object HTML_Table   The HTML Table object for the DataGrid
     */
    function &getTable()
    {
        return $this->_container;
    }   

    /**
     * Handles building the header of the DataGrid
     *
     * @access  private
     * @return  void
     * @todo    Redesign/Rework the header URL building.
     */
    function buildHeader ()
    {
        $prefix = $this->_requestPrefix;
        // Build the list of common get parameters
        $common   = $this->_options['extraVars'];
        $ignore   = $this->_options['excludeVars'];
        $ignore[] = $prefix . 'orderBy';
        $ignore[] = $prefix . 'page';
        $ignore[] = $prefix . 'direction';
        foreach ($_GET as $key => $val) {
            if (!in_array ($key, $ignore)) {
                $common[$key] = $val;
            }
        }

        for ($col = 0; $col < $this->_columnsNum; $col++) {
            $field = $this->_columns[$col]['field'];
            $label = $this->_columns[$col]['label'];

            //Define Content
            if (!in_array ($field, $this->_options['disableColumnSorting'])) {
                // Determine Direction
                if ($this->_currentSortField == $field && 
                    $this->_currentSortDirection == 'ASC') {
                    $direction = 'DESC';
                } else {
                    $direction = 'ASC';
                }

                // Build list of GET variables
                $get = array();
                $get[$prefix . 'orderBy'] = $field;
                $get[$prefix . 'direction'] = $direction;
                $get[$prefix . 'page'] 
                    = $this->_options['sortingResetsPaging'] ? 1 : $this->_page;

                // Build Link URL
                $url = $this->_options['selfPath'] . '?';

                // Merge common and column-specific GET variables
                $url .= http_build_query (array_merge ($common, $get));
                
                // Build HTML Link
                $str = "<a href=\"$url\">$label";
                $iconVar = "sortIcon" . 
                           (is_null ($this->_currentSortDirection) ? 'ASC' : $this->_currentSortDirection);
                if (($this->_options[$iconVar] != '') && 
                    ($this->_currentSortField == $field)) {
                    $str .= ' ' . $this->_options[$iconVar];
                }
                $str .= '</a>';
                
            } else {
                $str = $label;
            }

            // Print Content to HTML_Table
            $this->_tableHeader->setHeaderContents(0, $col, $str);
            if (isset ($this->options['headersAttributes'][$field])) {
                $this->_tableHeader->setCellAttributes(0, $col, $this->options['headersAttributes'][$field]);
            }
        }
    }

    /**
     * Handles building the body of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function buildBody()
    {
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $value = $this->_records[$row][$col];
                
                /* Right-align the content if it is numeric */
                $attributes = ($this->_options['autoAlign'] and is_numeric ($value)) 
                            ? array('align' => 'right')
                            : array();
                
                // Set Content in HTML_Table
                $this->_tableBody->setCellContents($row, $col, $value);
                if ($attributes) {
                    $this->_tableBody->setCellAttributes($row, $col, $attributes);
                }
            }
        }

        // output empty rows
        if ($this->_options['fillWithEmptyRows'] && !is_null($this->_pageLimit)) {
            for ($row = $this->_recordsNum; $row < $this->_pageLimit; $row++) {
                for ($col = 0; $col < $this->_columnsNum; $col++) {
                    $this->_tableBody->setCellAttributes($row, $col, $this->_options['emptyRowAttributes']);
                    $this->_tableBody->setCellContents($row, $col, '&nbsp;');
                }
            }
        }
        
        // Define Alternating Row attributes
        if ($this->_options['evenRowAttributes'] 
            or $this->_options['oddRowAttributes']) {

            $this->_tableBody->altRowAttributes(
                0,
                $this->_options['evenRowAttributes'],
                $this->_options['oddRowAttributes'],
                TRUE
            );
        }
    }
   
    function defaultCellFormatter ($value)
    {
        return htmlspecialchars($value);
    }

    function flatten ()
    {
        return $this->_container->toHTML();
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
                                                $this->_options['extraVars']);
        } else {
            $options['extraVars'] = $this->_options['extraVars'];
        }
        
        if (isset ($options['excludeVars'])) {
            $options['excludeVars'] = array_merge($options['excludeVars'],
                                                  $this->_options['excludeVars']);
        } else {
            $options['excludeVars'] = $this->_options['excludeVars'];
        }
        
        $this->_buildPaging($options);

        // Return paging html
        return $this->_pager->links;
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
        $defaults = array('totalItems' => $this->_totalRecordsNum,
                          'perPage' => is_null($this->_pageLimit) 
                                       ? $this->_totalRecordsNum 
                                       : $this->_pageLimit,
                          'urlVar' => $this->_requestPrefix . 'page',
                          'currentPage' => $this->_page); 
        $options = array_merge($defaults, $options);
        $this->_pager =& Pager::factory($options);
    }    
}

?>
