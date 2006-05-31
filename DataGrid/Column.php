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
     * @param   string      $columnName     The name of the column to be printed
     * @param   string      $fieldName      The name of the field for the column
     *                                      to be mapped to
     * @param   string      $orderBy        The field to order the data by
     * @param   string      $attribs        The HTML attributes for the TR tag
     * @param   string      $autoFillValue  The value to use for the autoFill
     * @param   mixed       $formatter      Formatter callback. See setFormatter()
     * @param   array       $formatterArgs  Associative array of arguments 
     *                                      passed to the formatter callback
     * @see http://www.php.net/manual/en/language.pseudo-types.php
     * @see setFormatter()
     * @access  public
     */
    function Structures_DataGrid_Column($columnName, $fieldName = null,
                                        $orderBy = null, $attribs = array(),
                                        $autoFillValue = null,
                                        $formatter = null,
                                        $formatterArgs = array())
    {
        $this->columnName = $columnName;
        $this->fieldName = $fieldName;
        $this->orderBy = $orderBy;
        $this->attribs = $attribs;
        $this->autoFillValue = $autoFillValue;
        if (!is_null($formatter)) {
            $this->setFormatter($formatter, $formatterArgs);
        }
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
     *                              the callback function
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
    function formatter($record)
    {
        // Define the parameter list
        $paramList = array();
        $paramList['record'] = $record;
        $paramList['fieldName'] = $this->fieldName;
        $paramList['columnName'] = $this->columnName;
        $paramList['orderBy'] = $this->orderBy;
        $paramList['attribs'] = $this->attribs;

        // Call the formatter
        return call_user_func ($this->formatter, 
                               array_merge ($this->formatterArgs, $paramList));
    }

}

?>
