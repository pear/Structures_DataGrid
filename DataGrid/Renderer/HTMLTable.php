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

require_once ('HTML/Table.php');

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
     * The table header background color
     * @var string
     */
    var $headerBgColor;

    /**
     * The dark row background color
     * @var string
     */
    var $rowDarkBgColor;

    /**
     * The light row background color
     * @var string
     */
    var $rowLightBgColor;

    /**
     * An associative array containing each attribute of the table
     * @var array
     */
    var $attrs;

    /**
     * A boolean value to determine if empty rows should be printed.
     * @var array
     */
    var $allowEmptyRows;

    /**
     * An associative array containing the attributes for empty rows
     * @var array
     */
    var $emptyRowAttributes = array();

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
        $this->headerBgColor = '#FFFFFF';
        $this->rowDarkBgColor = '#FFFFFF';
        $this->rowLightBgColor = '#FFFFFF';

        $this->attrs = array('cellpadding' => 4,
                             'cellspacing' => 0,
                             'border' => 0);
        $this->_table = new HTML_Table($this->attrs);
    }

    /**
     * Set a table attribute
     *
     * @access public
     * @param  string   $name    The CSS class to use for the table.
     */
    function setTableAttribute($attr, $value)
    {
        $this->attrs[$attr] = $value;
        $this->_table->_attributes = $this->attrs;
    }

    /**
     * Define the table's header bgcolor
     *
     * @access public
     * @param  string    $bgColor   The header bgcolor to use for the table.
     */
    function setTableHeaderBgColor($bgColor)
    {
        $this->headerBgColor = $bgColor;
    }

    /**
     * Define the table's row dark color
     *
     * @access public
     * @param  string    $bgColor   The color to use for the dark table row.
     */
    function setTableRowDarkBgColor($bgColor)
    {
        $this->rowDarkBgColor = $bgColor;
    }

    /**
     * Define the table's row light color
     *
     * @access public
     * @param  string    $bgColor   The color to use for the light table row.
     */
    function setTableRowLightBgColor($bgColor)
    {
        $this->rowLightBgColor = $bgColor;
    }

    /**
     * Define the table's autofill value.  This value appears only in an empty table cell.
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
     * @return  void
     */
    function render(&$dg)
    {
        $this->_dg = &$dg;

        // Define Table Header
        $this->_buildHTMLTableHeader();

        // Build Table Data
        $this->_buildHTMLTableBody();

        // Define Alternating Row attributes
        $lightRow = array('bgcolor' => $this->rowLightBgColor);
        $darkRow = array('bgcolor' => $this->rowDarkBgColor);
        $this->_table->altRowAttributes(1, $lightRow, $darkRow);

        // Print the table
        echo $this->_table->toHTML();
    }

    /**
     * Handles building the header of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function _buildHTMLTableHeader()
    {
        $cnt = 0;
        foreach ($this->_dg->columnSet as $column) {
            //Define Content
            if ($column->orderBy != null) {
                if (stristr($_SERVER['PHP_SELF'], '?')) {
                    $url = $_SERVER['PHP_SELF'] . '&orderBy=';
                } else {
                    $url = $_SERVER['PHP_SELF'] . '?orderBy=';
                }

                if ($this->_dg->sortArray[1] == 'ASC') {
                    $direction = '&direction=DESC';
                } else {
                    $direction = '&direction=ASC';
                }

                $str = '<a href="' . $url . $column->orderBy . $direction . '"><b>' .
                       $column->columnName . '</b></a>';
            } else {
                $str = '<b>' . $column->columnName . '</b>';
            }

            // Print Content to HTML_Table
            $this->_table->setHeaderContents(0, $cnt, $str);
            $this->_table->setCellAttributes(0, $cnt, $column->attribs);

            $cnt++;
        }

        // Define Table Header attributes
        $attr = array('bgcolor' => $this->headerBgColor);
        $this->_table->setRowAttributes(0, $attr);
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
                        if ($column->formatter != null) {
                            $content = $column->formatter($row);
                        } elseif ($column->fieldName == null) {
                            if ($column->autoFill != null) {
                                $content = $column->autoFill;
                            } else {
                                $content = $column->columnName;
                            }
                        } else {
                            $content = $row[$column->fieldName];
                        }

                        // Print Content to HTML_Table
                        $this->_table->setCellContents($rowCnt, $cnt, $content);
                        $this->_table->setCellAttributes($rowCnt, $cnt, $column->attribs);

                        $cnt++;
                    }
                } else {
                    // Determine if empty row should be printed
                    if ($this->allowEmptyRows) {
                        $rowCnt++;
                        $this->_table->setRowAttributes($rowCnt, $this->emptyRowAttributes, false);
                        $this->_table->setCellContents($rowCnt, 0, '&nbsp;');
                    }
                }
            }
        }
    }

    /**
     * Handles the printing of the page list for the DataGrid in HTML.
     *
     * @access  public
     * @param   string $seperator   The string to use to seperate each page link
     * @return  void
     * @todo    Investigate HTML::Pager
     */
    function printPaging($seperator = '|')
    {
        // Generate Paging
        $this->_dg->buildPaging();

        // Create base url for link
        if (isset($_SERVER['QUERY_STRING'])) {
            $url = $_SERVER['PHP_SELF'] . '?';
            $queryString = explode('&', $_SERVER['QUERY_STRING']);
            foreach ($queryString as $value) {
                if (!strstr($value, 'page') && ($value != '')) {
                    $url .= $value . '&';
                }
            }
            $url .= 'page=';
        } else {
            $url = $_SERVER['PHP_SELF'] . '?page=';
        }

        // Print back link
        if ($this->_dg->page > 1) {
            echo '<a href="' . $url . ($this->_dg->page - 1) . '"><<</a>&nbsp;&nbsp;&nbsp;';
        }

        $cnt = 0;
        foreach ($this->_dg->pageList as $page => $link) {
            if ($link != '') {
                echo '<a href="' . $url . $page . "\">&nbsp;$page&nbsp;</a>";
            } else {
                echo "<b>&nbsp;$page&nbsp;</b>";
            }

            if ($cnt+1 < count($this->_dg->pageList)) {
                echo "&nbsp;$seperator&nbsp;";
            }

            $cnt++;
        }

        if ($this->_dg->page < count($this->_dg->pageList)) {
            echo '&nbsp;&nbsp;&nbsp;<a href="' . $url . ($this->_dg->page + 1) . '">>></a>';
        }
    }

}

?>
