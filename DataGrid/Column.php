<?php
/**
 * Structures_DataGrid_Column Class
 * 
 * <pre>
 * +----------------------------------------------------------------------+
 * | PHP version 4                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2005 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | This source file is subject to version 2.0 of the PHP license,       |
 * | that is bundled with this package in the file LICENSE, and is        |
 * | available through the world-wide-web at                              |
 * | http://www.php.net/license/2_02.txt.                                 |
 * | If you did not receive a copy of the PHP license and are unable to   |
 * | obtain it through the world-wide-web, please send a note to          |
 * | license@php.net so we can mail you a copy immediately.               |
 * +----------------------------------------------------------------------+
 * | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
 * |          Olivier Guilyardi <olivier@samalyse.com>                    |
 * |          Mark Wiesemann <wiesemann@php.net>                          |
 * +----------------------------------------------------------------------+
 * </pre>
 *
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid
 * @category Structures
 */

/**
 * Structures_DataGrid_Column Class
 *
 * This class represents a single column for the DataGrid.
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Column
{
    /**
     * The name (label) of the column
     * @var string
     */
    var $columnName;

    /**
     * The name of the field to map to
     * @var string
     */
    var $fieldName;

    /**
     * The field name to order by. Optional
     * @var array
     */
    var $orderBy;

    /**
     * The attributes to use for the cell. Optional
     * @var array
     */
    var $attribs;

    /**
     * The value to be used if a cell is empty
     * @var string
     */
    var $autoFillValue;

    /**
     * A callback function to be called for each cell to modify the output
     * @var     mixed
     * @access  private
     */
    var $formatter;
    
    /**
     * User defined parameters passed to the formatter callback function
     * @var     array
     * @access  private
     */
    var $formatterArgs;

    /**
     * Constructor
     *
     * Creates default table style settings
     *
     * @param   string      $label          The label of the column to be printed
     * @param   string      $field          The name of the field for the column
     *                                      to be mapped to
     * @param   string      $orderBy        The field or expression to order the data by
     * @param   string      $attributes     The HTML attributes for the TD tag
     * @param   string      $autoFillValue  The value to use for the autoFill
     * @param   mixed       $formatter      Formatter callback. See setFormatter()
     * @param   array       $formatterArgs  Associative array of arguments 
     *                                      passed as second argument to the 
     *                                      formatter callback
     * @see http://www.php.net/manual/en/language.pseudo-types.php
     * @see Structures_DataGrid::addColumn()
     * @see setFormatter()
     * @access  public
     */
    function Structures_DataGrid_Column($label, 
                                        $field = null,
                                        $orderBy = null, 
                                        $attributes = array(),
                                        $autoFillValue = null,
                                        $formatter = null,
                                        $formatterArgs = array())
    {
        $this->columnName = $label;
        $this->fieldName = $field;
        $this->orderBy = $orderBy;
        $this->attribs = $attributes;
        $this->autoFillValue = $autoFillValue;
        if (!is_null($formatter)) {
            $this->setFormatter($formatter, $formatterArgs);
        }
    }

    /**
     * Get column label
     *
     * The label is the text rendered into the column header. 
     *
     * @return  string
     * @access  public
     */
    function getLabel()
    {
        return $this->columnName;
    }

    /**
     * Set column label
     *
     * The label is the text rendered into the column header. 
     *
     * @param   string      $str        Column label
     * @access  public
     */
    function setLabel($str)
    {
        $this->columnName = $str;
    }

    /**
     * Get name of the field for the column to be mapped to
     *
     * Returns the name of the field for the column to be mapped to
     *
     * @return  string
     * @access  public
     */
    function getField()
    {
        return $this->fieldName;
    }

    /**
     * Set name of the field for the column to be mapped to
     *
     * Defines the name of the field for the column to be mapped to
     *
     * @param   string      $str        The name of the field for the column to
     *                                  be mapped to
     * @access  public
     */
    function setField($str)
    {
        $this->fieldName = $str;
    }

    /**
     * Get the field name or the expression to order the data by
     *
     * Returns the name of the field to order the data by. With SQL based
     * datasources, this may be an SQL expression (function, etc..). 
     *
     * @return  string
     * @access  public
     */
    function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Set the field name or the expression to order the data by
     *
     * Set the name of the field to order the data by. With SQL based
     * datasources, this may be an SQL expression (function, etc..). 
     *
     * @param   string      $str  field name or expression 
     * @access  public
     */
    function setOrderBy($str)
    {
        $this->orderBy = $str;
    }

    /**
     * Get the column XML/HTML attributes 
     *
     * Return the attributes applied to all cells in this column.
     * This only makes sense for HTML or XML rendering
     *
     * @return  array   Attributes ; form: array(name => value, ...)
     * @access  public
     */
    function getAttributes()
    {
        return $this->attribs;
    }

    /**
     * Set the column XML/HTML attributes 
     *
     * Set the attributes to be applied to all cells in this column.
     * This only makes sense for HTML or XML rendering
     * 
     * @param   array   $attributes form: array(name => value, ...)
     * @access  public
     */
    function setAttributes($attributes)
    {
        $this->attribs = $attributes;
    }

    /**
     * Get auto fill value
     *
     * Returns the value to be printed if a cell in the column is null.
     *
     * @return  string
     * @access  public
     */
    function getAutoFillValue()
    {
        return $this->autoFillValue;
    }

    /**
     * Set auto fill value
     *
     * Defines a value to be printed if a cell in the column is null.
     *
     * @param   string      $str        The value to use for the autoFill
     * @access  public
     */
    function setAutoFillValue($str)
    {
        $this->autoFillValue = $str;
    }

    /**
     * Set Formatter
     *
     * Define a formatting callback function with optional arguments for 
     * this column.
     *
     * @param   mixed   $formatter  Callback PHP pseudo-type (Array or String)
     * @param   array   $arguments  Associative array of parameters passed to 
     *                              as second argument to the callback function
     * @return  mixed               PEAR_Error on failure 
     * @see http://www.php.net/manual/en/language.pseudo-types.php
     * @access  public
     */
    function setFormatter($formatter, $arguments = array())
    {
        $this->formatterArgs = $arguments;
        if (is_array($formatter)) {
            $formatter[1] = $this->_parseCallbackString ($formatter[1], 
                                                         $this->formatterArgs);
        } else {
            $formatter = $this->_parseCallbackString ($formatter, 
                                                      $this->formatterArgs);
        }
        if (is_callable ($formatter)) {
            $this->formatter = $formatter;
        } else {
            return PEAR::raiseError('Column formatter is not a valid callback');
        }
    }

    /**
     * Parse a callback function string
     *
     * This method parses a string of the type "myFunction($param1=foo,...)",
     * return the isolated function name ("myFunction") and fills $paramList 
     * with the extracted parameters (array('param1' => foo, ...))
     * 
     * @param   string  $callback   Callback function string
     * @param   array   $paramList  Reference to an array of parameters
     * @return  string              Function name
     * @access  private
     */
    function _parseCallbackString($callback, &$paramList)
    {   
        if ($size = strpos($callback, '(')) {
            $orig_callback = $callback;
            // Retrieve the name of the function to call
            $callback = substr($callback, 0, $size);
            if (strstr($callback, '->')) { 
                $callback = explode('->', $callback);
            } elseif (strstr($callback, '::')) {
                $callback = explode('::', $callback);
            }

            // Build the list of parameters
            $length = strlen($orig_callback) - $size - 2;
            $parameters = substr($orig_callback, $size + 1, $length);
            $parameters = ($parameters === '') ? array() : split(',', $parameters);

            // Process the parameters
            foreach($parameters as $param) {
                if ($param != '') {
                    $param = str_replace('$', '', $param);
                    if (strpos($param, '=') != false) {
                        $vars = split('=', $param);
                        $paramList[trim($vars[0])] = trim($vars[1]);
                    } else {
                        $paramList[$param] = $$param;
                    }
                }
            }
        }

        return $callback;
    }
    
    /**
     * Formatter
     *
     * This method is not meant to be called by user-space code.
     * 
     * Calls a predefined function to develop custom output for the column. The
     * defined function can accept parameters so that each cell in the column
     * can be unique based on the record.  The function will also automatically
     * receive the record array as a parameter.  All parameters passed into the
     * function will be in one array.
     *
     * @access  public
     */
    function formatter($record, $row, $col)
    {
        // Define the parameter list
        $paramList = array();
        $paramList['record'] = $record;
        $paramList['fieldName'] = $this->fieldName;
        $paramList['columnName'] = $this->columnName;
        $paramList['orderBy'] = $this->orderBy;
        $paramList['attribs'] = $this->attribs;
        $paramList['currRow'] = $row;
        $paramList['currCol'] = $col;

        // Call the formatter
        if (isset($GLOBALS['_STRUCTURES_DATAGRID']['column_formatter_BC'])) {
            $paramList = array_merge($this->formatterArgs, $paramList);
            $formatted = call_user_func($this->formatter, $paramList);
        } else {
            if ($this->formatterArgs) {
                $formatted = call_user_func($this->formatter, $paramList, 
                                            $this->formatterArgs);
            } else {
                $formatted = call_user_func($this->formatter, $paramList);
            }
        }

        return $formatted;
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
