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
require_once 'Pager/Pager.php';

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
     * The complete path for the sorting links.  If not defined, PHP_SELF is used
     * @var string
     */
    var $path;

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
        $this->_table->setRowAttributes(0, $attribs);
    }

    /**
     * Define the table's row dark color
     *
     * @access public
     * @param  string    $bgColor   The color to use for the dark table row.
     */
    function setTableOddRowAttributes($attribs)
    {
        $this->oddRowAttributes = $attribs;
    }

    /**
     * Define the table's row light color
     *
     * @access public
     * @param  string    $bgColor   The color to use for the light table row.
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
     * Generates the HTML for the DataGrid
     *
     * @access  public
     * @return  string      The HTML of the DataGrid
     */
    function render(&$dg)
    {
        $this->_dg = &$dg;

        // Define Table Header
        if ($this->header) {
            $this->_buildHTMLTableHeader();
        }

        // Build Table Data
        $this->_buildHTMLTableBody();

        // Define Alternating Row attributes
        $this->_table->altRowAttributes(1,
                                        $this->evenRowAttributes,
                                        $this->oddRowAttributes);

        // Print the table
        return $this->_table->toHTML();
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
                if ($this->_dg->sortArray[1] == 'ASC') {
                    $direction = 'direction=DESC';
                } else {
                    $direction = 'direction=ASC';
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
                            if (stristr($element, 'orderBy')) {
                                $url .= 'orderBy=' . $column->orderBy;
                                $orderByExists = true;
                            } elseif (stristr($element, 'direction')) {
                                $url .= $direction;
                            } else {
                                $url .= $element;
                            }
                        }
                        $i++;
                        if ($i < count($qString)) {
                            $url .= '&';
                        }
                    }

                    if (!isset($orderByExists)) {
                        $url .= '&orderBy=' . $column->orderBy . '&' . $direction;
                    }
                } else {
                    $url .= 'orderBy=' . $column->orderBy . '&' . $direction;
                }

                $str = '<a href="' . $url . '"><b>' . $column->columnName . '</b></a>';
            } else {
                $str = '<b>' . $column->columnName . '</b>';
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
        if (count($this->_dg->recordSet)) {

            // Determine looping values
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

            // Begin loop
            for ($i = $begin; $i < $limit; $i++) {
                $row = $this->_dg->recordSet[$i];
                if ($row != null) {
                    // Print Row
                    $cnt = 0;
                    foreach ($this->_dg->columnSet as $column) {
                        $rowCnt = ($i-$begin)+1;

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
                        $this->_table->setRowAttributes($rowCnt,
                            $this->emptyRowAttributes, false);
                        $this->_table->setCellContents($rowCnt, 0, '&nbsp;');
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
     * @return  void
     * @see     HTML::Pager
     */
    function getPaging($mode = 'Sliding', $separator = '|', $prev = '<<',
                       $next = '>>', $delta = 5, $attrs = null)
    {
        // Generate Paging
        $options = array('mode' => $mode,
                         'delta' => $delta,
                         'separator' => $separator,
                         'prevImg' => $prev,
                         'nextImg' => $next);
        if (is_array($attrs)) {
            $options = array_merge($options, $attrs);
        }
        $this->_dg->buildPaging($options);

        // Return paging html
        return $this->_dg->pager->links;
    }

}

?>
