<?php
/**
 * XUL Rendering Driver
 * 
 * PHP version 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Andrew Nagy <asnagy@webitecture.org>,
 *                          Olivier Guilyardi <olivier@samalyse.com>,
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
 * @category Structures
 * @package  Structures_DataGrid_Renderer_XUL
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'XML/Util.php';

/**
 * XUL Rendering Driver
 *
 * SUPPORTED OPTIONS:
 *
 * - selfPath:      (string) The complete path for sorting links
 *                           (default: $_SERVER['PHP_SELF'])
 * - columnAttributes:   (-) IGNORED
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: no
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 * - Streaming:         no
 * - Object Preserving: no
 * 
 * GENERAL NOTES:
 * 
 * This renderer class will render a XUL listbox.
 * For additional information on the XUL Listbox, refer to this url:
 * http://www.xulplanet.com/references/elemref/ref_listbox.html
 *
 * You have to setup your XUL document, just as you would with an HTML
 * document. This driver will only generated the <listbox> element and
 * content.
 * 
 * @version     $Revision$
 * @example     xul.php Using the XUL renderer
 * @author      Andrew S. Nagy <asnagy@webitecture.org>
 * @author      Olivier Guilyardi <olivier@samalyse.com>
 * @author      Mark Wiesemann <wiesemann@php.net>
 * @access      public
 * @category    Structures
 * @package     Structures_DataGrid_Renderer_XUL
 * @todo        Implement PEAR::XML_XUL upon maturity
 */
class Structures_DataGrid_Renderer_XUL extends Structures_DataGrid_Renderer
{
    /**
     * The generated XUL data
     * @var string
     * @access protected
     */
    var $_xul = '';
    
    /**
     * Constructor
     *
     * Initialize default options
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XUL()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'selfPath' => htmlspecialchars($_SERVER['PHP_SELF'])
            )
        );
        $this->_setFeatures(
            array(
                'outputBuffering' => true,
            )
        );
    }

    /**
     * Initialize the XUL listbox
     *
     * @access protected
     */
    function init()
    {
        $this->_xul = "<listbox rows=\"" . $this->_pageLimit . "\">\n";
    }

    /**
     * Build the <listhead> grid header 
     *
     * @access  protected
     * @return  void
     */
    function buildHeader()
    {
        $this->_xul .= "  <listhead>\n";
        for ($col = 0; $col < $this->_columnsNum; $col++) {
            $field = $this->_columns[$col]['field'];
            $label = $this->_columns[$col]['label'];

            if (in_array($field, $this->_sortableFields)) {
                reset($this->_currentSort);
                if (list($currentField, $direction) = each($this->_currentSort) 
                     and $currentField == $field) {
                    if ($direction == 'ASC') {
                        // The data is currently sorted by $column, ascending.
                        // That means we want $dirArg set to 'DESC', for the next
                        // click to trigger a reverse order sort, and we need 
                        // $dirCur set to 'ascending' so that a neat xul arrow 
                        // shows the current "ASC" direction.
                        $dirArg = 'DESC'; 
                        $dirCur = 'ascending'; 
                    } else {
                        // Next click will do ascending sort, and we show a reverse
                        // arrow because we're currently descending.
                        $dirArg = 'ASC';
                        $dirCur = 'descending';
                    }
                } else {
                    // No current sort on this column. Next click will sort by 
                    // the default direction. We show no arrow.
                    $dirArg = $this->_defaultDirections[$field];
                    $dirCur = 'natural';
                }

                if ($handler = $this->_buildOnMoveCall($this->_page, 
                        array($field => $dirArg))) {
                    $onCommand = "oncommand=\"$handler\"";
                } else {
                    $onCommand = 
                        "oncommand=\"location.href='{$this->_options['selfPath']}?" 
                        . $this->_buildSortingHttpQuery($field, $dirArg, true)
                        . "'\"";
                }
                $sortDirection = "sortDirection=\"$dirCur\"";
            } else {
                $onCommand = '';
                $sortDirection = '';
            }

            $label = XML_Util::replaceEntities($label);
            $this->_xul .= '    <listheader label="' . $label . '" ' . 
                    "$sortDirection $onCommand />\n";
        }
        $this->_xul .= "  </listhead>\n";
    }
    
    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $this->_xul .= "  <listitem>\n";
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $value = $this->_records[$row][$col];

                // FIXME: 'ä' is displayed as '?' ==> encoding is required!
                // How to use the "encoding" option here ? 
                // Is it our responsibility ?
                $this->_xul .= '    ' .
                        XML_Util::createTag('listcell',
                                            array('label' => $value)) . "\n";
            }

            $this->_xul .= "  </listitem>\n";
        }
    }

    /**
     * Close the XUL listbox
     *
     * @access protected
     */
    function finalize()
    {
        $this->_xul .= "</listbox>\n";
    }
    
    /**
     * Returns the XUL for the DataGrid
     *
     * @access  public
     * @return  string      The XUL of the DataGrid
     */
    function toXUL()
    {
        return $this->getOutput();
    }

    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        return $this->_xul;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
