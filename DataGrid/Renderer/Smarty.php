<?php
/**
 * Smarty Rendering Driver
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Andrew Nagy <asnagy@webitecture.org>,
 *                          Olivier Guilyardi <olivier@samalyse.com>,
 *                          Mark Wiesemann <wiesemann@php.net>
 *                          Sascha Grossenbacher <saschagros@bluewin.ch>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * CVS file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_Renderer_Smarty
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid.php';
require_once 'Structures/DataGrid/Renderer.php';

/**
 * Smarty Rendering Driver
 *
 * SUPPORTED OPTIONS:
 * 
 * - selfPath:            (string) The complete path for sorting and paging links.
 *                                 (default: $_SERVER['PHP_SELF'])
 * - sortingResetsPaging: (bool)   Whether sorting HTTP queries reset paging.  
 * - convertEntities:     (bool)   Whether or not to convert html entities.
 *                                 This calls htmlspecialchars(). 
 * - varPrefix:           (string) Prefix for smarty variables and functions 
 *                                 assigned by this driver. Can be used in 
 *                                 conjunction with 
 *                                 Structure_DataGrid::setRequestPrefix() for
 *                                 displaying several grids on a single page.
 * - associative:         (bool)   By default the column set and the records
 *                                 are numerically indexed arrays. By setting 
 *                                 this option to true the keys will be field 
 *                                 names instead.
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: yes
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 * - Streaming:         no
 * - Object Preserving: yes
 *
 * GENERAL NOTES:
 *
 * To use this driver you need the Smarty template engine from 
 * http://smarty.php.net
 *
 * This driver does not support the render() method, it is only able to:
 *
 * Either fill() a Smarty object by assigning variables and registering 
 * the {getPaging} smarty function. It's up to you to call Smarty::display() 
 * after the Smarty object has been filled.
 *
 * Or return all variables as a PHP array from getOutput(), for maximum 
 * flexibility, so that you can assign them the way you like to your Smarty
 * instance.
 *
 * This driver assigns the following Smarty variables: 
 * <code>
 * - $columnSet:       array of columns specifications
 *                     structure: 
 *                          array ( 
 *                              0 => array (
 *                                  'name'       => field name,
 *                                  'label'      => column label,
 *                                  'link'       => sorting link,
 *                                  'attributes' => attributes string,
 *                                  'direction'  => 'ASC', 'DESC' or '',
 *                                  'onclick'    => jsHandler call
 *                              ),
 *                              ... 
 *                          )
 * - $recordSet:       array of records values
 * - $currentPage:     current page (starting from 1)
 * - $nextPage:        next page
 * - $previousPage:    previous page
 * - $recordLimit:     number of rows per page
 * - $pagesNum:        number of pages
 * - $columnsNum:      number of columns
 * - $recordsNum:      number of records in the current page
 * - $totalRecordsNum: total number of records
 * - $firstRecord:     first record number (starting from 1)
 * - $lastRecord:      last record number (starting from 1)
 * - $currentSort:     array with column names and the directions used for sorting
 * - $datagrid:        a reference that you can pass to {getPaging}
 * </code>
 * 
 * This driver registers a Smarty custom function named getPaging
 * that can be called from Smarty templates with {getPaging} in order
 * to print paging links. This function accepts the same parameters as the
 * pagerOptions option of Structures_DataGrid_Renderer_Pager.
 *
 * {getPaging} accepts an optional "datagrid" parameter 
 * which you can pass the $datagrid variable, to display paging for an
 * arbitrary datagrid (useful with multiple dynamic datagrids on a single page).
 *
 * Object Records : this drivers preserves object records if provided. This means
 * that if your datasource provides objects instead of associative arrays as
 * records, you can access their properties and methods in your smarty template, 
 * with something like: {$recordSet[col]->getSomeInformation()}.
 *
 * @version  $Revision$
 * @example  smarty-simple.php Using the Smarty renderer
 * @example  smarty-simple.tpl Smarty template with sorting and paging (smarty-simple.tpl)
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Sascha Grossenbacher <saschagros@bluewin.ch>
 * @access   public 
 * @package  Structures_DataGrid_Renderer_Smarty
 * @see      Structures_DataGrid_Renderer_Pager
 * @category Structures
 */
class Structures_DataGrid_Renderer_Smarty extends Structures_DataGrid_Renderer
{
    /**
     * Variables that get assigned into the Smarty container
     * @var array Associative array with smarty var names as keys
     */
    var $_data;
    
    /**
     * Smarty container
     * @var object Smarty object
     */
    var $_smarty = null;
    
    /**
     * Constructor
     *
     * @access  public
     */
    function Structures_DataGrid_Renderer_Smarty()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'selfPath'            => htmlspecialchars($_SERVER['PHP_SELF']),
                'convertEntities'     => true,
                'sortingResetsPaging' => true,
                'varPrefix'           => '',
                'associative'         => false,  
            )
        );

        $this->_setFeatures(
            array(
                'outputBuffering' => true,
                'objectPreserving' => true,
            )
        );
    }
        
    /**
     * Attach an already instantiated Smarty object
     * 
     * @param  object $smarty Smarty container
     * @return mixed True or PEAR_Error
     */
    function setContainer(&$smarty)
    {
        $this->_smarty =& $smarty;
        return true;
    }

    /**
     * Attach a Smarty instance
     * 
     * @deprecated Use setContainer() instead
     * @param object Smarty instance
     * @access public
     */
    function setSmarty(&$smarty)
    {
        return $this->setContainer($smarty);
    }
    
    /**
     * Return the currently used Smarty object
     *
     * @return object Smarty or PEAR_Error object
     */
    function &getContainer()
    {
        return $this->_smarty;
    }
    
    /**
     * Initialize the Smarty container
     * 
     * @access protected
     */
    function init()
    {
        $p = $this->_options['varPrefix'];
        $this->_data = array(
            "{$p}currentPage"       => $this->_page,
            "{$p}nextPage"          => ($this->_page < $this->_pagesNum) ? $this->_page + 1 : null,
            "{$p}previousPage"      => ($this->_page > 1) ? $this->_page - 1 : null,
            "{$p}recordLimit"       => $this->_pageLimit,
            "{$p}columnsNum"        => $this->_columnsNum,
            "{$p}recordsNum"        => $this->_recordsNum,
            "{$p}totalRecordsNum"   => $this->_totalRecordsNum,
            "{$p}pagesNum"          => $this->_pagesNum,
            "{$p}firstRecord"       => $this->_firstRecord,
            "{$p}lastRecord"        => $this->_lastRecord,
            "{$p}currentSort"       => $this->_currentSort,
        );                
    }

    /**
     * Build the header 
     *
     * @param   array $columns Columns' fields names and labels  
     * @access  protected
     * @return  void
     */
    function buildHeader(&$columns)
    {
        $prepared = array();
        foreach ($columns as $index => $spec) {
            $key = $this->_options['associative'] ? $spec['field'] : $index;
            if (in_array($spec['field'], $this->_sortableFields)) {
                reset($this->_currentSort);
                if ((list($currentField, $currentDirection) = each($this->_currentSort))
                    && isset($currentField)
                    && $currentField == $spec['field']
                   ) {
                    if ($currentDirection == 'ASC') {
                        $direction = 'DESC';
                    } else {
                        $direction = 'ASC';
                    }
                    $prepared[$key]['direction'] = $currentDirection;
                } else {
                    $prepared[$key]['direction'] = '';
                    $direction = $this->_defaultDirections[$spec['field']];
                }
                $page = $this->_options['sortingResetsPaging'] ? 1 : $this->_page;
                $extra = array('page' => $page); 
                // Check if NUM is enabled
                if ($this->_urlMapper) {
                    $prepared[$key]['link'] = $this->_buildMapperURL($spec['field'], 
                                                                       $direction, 
                                                                       $page);
                } else {
                    $query = $this->_buildSortingHttpQuery($spec['field'], 
                                                       $direction, true, $extra);
                    $prepared[$key]['link'] = "{$this->_options['selfPath']}?$query";
                }
                $prepared[$key]['onclick'] = $this->_buildJsHandler($page, 
                        array($spec['field'] => $direction));
            } else {
                $query = '';
                $prepared[$key]['link'] = "";
            }
            $prepared[$key]['name']   = $spec['field'];
            $prepared[$key]['label']  = $spec['label'];

            $prepared[$key]['attributes'] = "";
            if (isset($this->_options['columnAttributes'][$spec['field']])) {
                foreach ($this->_options['columnAttributes'][$spec['field']] 
                            as $name => $value) {
                    $value = htmlspecialchars($value, ENT_COMPAT, 
                                              $this->_options['encoding']);
                    $prepared[$key]['attributes'] .= "$name=\"$value\" "; 
                }
            }
        }

        $this->_data[$this->_options['varPrefix'] . 'columnSet'] = $prepared;
    }
    
    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        if ($this->_options['associative']) {
            $associative = array();
            foreach ($this->_records as $row => $rec) {
                if (is_array($rec)) { // object records are left untouched
                    $associative[$row] = array();
                    foreach ($this->_columns as $col => $spec) {
                        $associative[$row][$spec['field']] = $rec[$col];
                    }
                } else {
                    $associative[$row] =& $this->_records[$row];
                }
            }
            $this->_data[$this->_options['varPrefix'] . 'recordSet'] 
                = $associative;
        } else {
            $this->_data[$this->_options['varPrefix'] . 'recordSet'] 
                = $this->_records;
        }
    }

    /**
     * Assign the computed variables to the Smarty container, if any
     *
     * @access protected
     * @return void
     */
    function finalize()
    {
        $p = $this->_options['varPrefix'];

        if ($this->_smarty) {
            foreach ($this->_data as $key => $val) {
                $this->_smarty->assign($key, $val);
            }

            $this->_smarty->assign("{$p}datagrid", $this->_getReference());

            $this->_smarty->register_function("{$p}getPaging",
                array(&$this, 'smartyGetPaging'));
        } else {
            $this->_data["{$p}datagrid"] = $this->_getReference();
        }
    }

    /**
     * Return the computed variables
     *
     * @access protected
     * @return array Array with smarty variable names as keys
     */
    function flatten()
    {
        return $this->_data;
    }

    /**
     * Discard the unsupported render() method
     * 
     * This Smarty driver does not support the render() method.
     * It is required to use the setContainer() (or 
     * Structures_DataGrid::fill()) method in order to do anything
     * with this driver.
     * 
     */
    function render()
    {
        return $this->_noSupport(__FUNCTION__);
    }

    /**
     * Smarty custom function "getPaging"
     *
     * This is only meant to be called from a smarty template, using the
     * expression: {getPaging <options>}
     *
     * <options> are any Pager::factory() options
     *
     * @param array  $params Options passed from the Smarty template
     * @param object $smarty Smarty object
     * @return string Paging HTML links
     * @access public
     */
    function smartyGetPaging($params, &$smarty)
    {
        // Load and get output from the Pager rendering driver
        $driver =& Structures_DataGrid::loadDriver('Structures_DataGrid_Renderer_Pager');

        // Propagate the selfPath option. Do not override user params
        if (!isset($params['path']) && !isset($params['filename'])) {
            $params['path'] = dirname($this->_options['selfPath']);
            $params['fileName'] = basename($this->_options['selfPath']);
            $params['fixFileName'] = false;
        }

        // Use a different renderer if provided
        if (isset($params['datagrid'])) {
            $renderer =& $this->_getReference($params['datagrid']);
            unset($params['datagrid']);
        } else {
            $renderer =& $this; 
        }

        $driver->setupAs($renderer, $params);
        $driver->build(array(), 0);
        return $driver->getOutput();
    }

    /**
     * Return a renderer reference by id or create a new id
     *
     * @param  int      Renderer id
     * @return mixed    New id or renderer object
     */
    function &_getReference($id = null)
    {
        static $references = array();
        
        if (!is_null($id)) {
            return $references[$id - 1];
        } else {
            $references[] =& $this;
            $id = count($references);
            return $id;
        }
    }

    /**
     * Default formatter for all cells
     * 
     * @param string  Cell value 
     * @return string Formatted cell value
     * @access protected
     */
    function defaultCellFormatter($value)
    {
        return $this->_options['convertEntities']
               ? htmlspecialchars($value, ENT_COMPAT, $this->_options['encoding'])
               : $value;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
