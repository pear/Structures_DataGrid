<?php
/**
 * HTML table rendering driver with a checkbox for each row of the grid
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Olivier Guilyardi <olivier@samalyse.com>,
 *                          Mark Wiesemann <wiesemann@php.net>
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
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_Renderer_CheckableHTMLTable
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/Renderer/HTMLTable.php';    

/**
 * HTML table rendering driver with a checkbox for each row of the grid
 *
 * Driver for rendering the DataGrid as an HTML table with a checkbox for
 * each row
 *
 * SUPPORTED OPTIONS:
 *
 * - form:                (object) Instance of a HTML_QuickForm object.
 * - formRenderer:        (object) Instance of a HTML_QuickForm_Renderer_QuickHtml
 *                                 object.
 * - inputName:           (string) The HTML_QuickForm element name for the
 *                                 checkboxes.
 * - primaryKey:          (string) The name of the primary key. This value is
 *                                 used for the checkboxes.
 *                  
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: yes
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 * - Streaming:         no
 *
 * GENERAL NOTES:
 *
 * This driver puts a checkbox for each row of the table into the first column.
 * By default, a new column with the value of the 'inputName' option is added
 * for the checkboxes. If you want to customize this column, you can add a
 * column yourself as it is shown in the example.
 *
 * @example  checkablehtmltable.php      Basic usage
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_Renderer_CheckableHTMLTable
 * @category Structures
 */
class Structures_DataGrid_Renderer_CheckableHTMLTable
    extends Structures_DataGrid_Renderer_HTMLTable
{
    /**
     * Constructor
     *
     * Build default values
     *
     * @access  public
     */
    function Structures_DataGrid_Renderer_CheckableHTMLTable() {
        parent::Structures_DataGrid_Renderer_HTMLTable();
        $this->_addDefaultOptions(
            array(
                'form'          => null,
                'formRenderer'  => null,
                'inputName'     => 'checkedItems',
                'primaryKey'    => 'id',
            )
        );
    }

    /**
     * Build the body
     *
     * @access  protected
     * @return  void or PEAR_Error
     */
    function buildBody()
    {
        // if there is no user generated column for the checkboxes, add a new
        // column using the values from the 'inputName' option
        if (count($this->_records[0]) != count($this->_columns)) {
            $checkableColumn = array(
                'field' => $this->_options['inputName'], 
                'label' => $this->_options['inputName'],  
            );
            $this->_columns = array_merge(array($checkableColumn), $this->_columns);
            $this->_columnsNum++;
        }

        $idColIndex = 0;
        for ($i = 0; $i < $this->_columnsNum; $i++) {
            if ($this->_columns[$i]['field'] == $this->_options['primaryKey']) {
                $idColIndex = $i; 
                break;
            }
        }
        for ($i = 0; $i < $this->_recordsNum; $i++) {
            $idValue = $this->_records[$i][$idColIndex];
            $this->_options['form']->addElement(
                    'advcheckbox', 
                    "{$this->_options['inputName']}[$i]", 
                    null, 
                    null, 
                    null, 
                    $idValue
            );
        }

        $this->_options['form']->accept($this->_options['formRenderer']);
        parent::buildBody();
    }

    /**
     * Build a body row
     *
     * @param int   $index Row index (zero-based)
     * @param array $data  Record data. 
     *                     Structure: array(0 => <value0>, 1 => <value1>, ...)
     * @return void or PEAR_Error
     * @access protected
     */
    function buildRow($index, $data)
    {
        $data[0] = $this->_options['formRenderer']->elementToHtml("{$this->_options['inputName']}[$index]");
        parent::buildRow($index, $data);
    }

}

?>
