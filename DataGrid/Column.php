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

/**
 * Structures_DataGrid_Column Class
 *
 * This class represents a single column for the DataGrid.
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Column
{
    /**
     * The name of the column
     * @var array
     */
    var $columnName;

    /**
     * The name of the field to map to
     * @var array
     */
    var $fieldName;

    /**
     * The field name to order by. Optional
     * @var array
     */
    var $orderBy;

    /**
     * The attributes of the td tag. Optional
     * @var array
     */
    var $attribs;

    /**
     * The URL for the link. Optional
     * @var array
    var $link;

     * The field to be used as the id for the link
     * @var array
    var $linkField;
     */

    /**
     * The value to be used if a cell contains a null value
     * @var array
     */
    var $autoFill;

    /**
     * A function to be called for each cell to modify the output
     * @var array
     */
    var $formatter;

    /**
     * Constructor
     *
     * Creates default table style settings
     *
     * @access public
     */
//    function DataGrid_Column($columnName, $fieldName, $orderBy = null,
//                             $attribs = array(), $link = null,
//                             $linkField = null, $autoFill = null,
//                             $formatter = null)
    function Structures_DataGrid_Column($columnName, $fieldName, $orderBy = null,
                             $attribs = array(), $autoFill = null,
                             $formatter = null)
    {
        $this->columnName = $columnName;
        $this->fieldName = $fieldName;
        $this->orderBy = $orderBy;
        $this->attribs = $attribs;
        //$this->link = $link;
        //$this->linkField = $linkField;
        $this->autoFill = $autoFill;
        $this->formatter = $formatter;
    }

    /**
     * Set Auto Fill
     *
     * Defines a value to be printed if a cell in the column is null.
     *
     * @access public
     */
    function setAutoFill($str)
    {
        $this->autoFill = $str;
    }

    /**
     * Set Formatter
     *
     * Defines the function and paramters to be called by the formatter method.
     *
     * @access public
     */
    function setFormatter($str)
    {
        $this->formatter = $str;
    }

    /**
     * Formatter
     *
     * Calls a predefined function to develop custom output for the column. It
     * can accepts paramaters so that each cell in the column can be unique
     * based on the record.
     *
     * @access public
     * @todo   add the record array to the formatter automatically
     */
    function formatter($record)
    {
        // Define any parameters
        if ($size = strpos($this->formatter, '(')) {
            // Retrieve the name of the function
            $formatter = substr($this->formatter, 0, $size);

            // Build the list of parameters
            $length = strlen($this->formatter) - $size - 2;
            $parameters = substr($this->formatter, $size + 1, $length);
            $parameters = split(',', $parameters);

            // Process the parameters
            $params = array();
            $param['record'] = $record;  // Automatically pass the record array in
            foreach($parameters as $param) {
                $param = str_replace('$', '', $param);
                if (strpos($param, '=') != false) {
                    $vars = split('=', $param);
                    $params[$vars[0]] = $vars[1];
                } else {
                    $params[$param] = $result;
                }
            }
        } else {
            $formatter = $this->formatter;
            $params = null;
        }

        // Call the formatter
        if (is_callable($formatter)) {
            $result = call_user_func_array($formatter, $params);
        } else {
            $result = new PEAR_Error('Unable to process formatter');
        }

        return $result;
    }

}

?>
