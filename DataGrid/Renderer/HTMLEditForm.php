<?php
/**
 * Record editing form rendering driver
 * 
 * PHP versions 4 and 5
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
 * @package  Structures_DataGrid_Renderer_HTMLEditForm
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'HTML/QuickForm.php';

/**
 * HTML form to edit a record
 *
 * SUPPORTED OPTIONS:
 * 
 * - textSubmit:        (string)    Label for the submit button
 * - columnAttributes:  (-)         IGNORED
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: yes
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 * - Streaming:         no
 *
 * @example htmleditform-basic.php      Basic usage
 * @example htmleditform-tableless.php  Usage with tableless renderer and DHTMLRules
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @access   public
 * @package  Structures_DataGrid_Renderer_HTMLEditForm 
 * @category Structures
 */
class Structures_DataGrid_Renderer_HTMLEditForm 
    extends Structures_DataGrid_Renderer
{
    /**
     * Rendering container
     * @var object HTML_QuickForm object
     * @access protected
     */
    var $_form;

    /**
     * Whether the container was provided by the user
     * @var bool
     * @access protected
     */
    var $_isUserContainer;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @access  public
     */
    function Structures_DataGrid_Renderer_HTMLEditForm()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'textSubmit'        => 'Submit',
            )
        );
        $this->_setFeatures(
            array(
                'outputBuffering' => true,
            )
        );
    }

    /**
     * Attach an already instantiated HTML_QuickForm object
     *
     * @var object HTML_QuickForm object
     * @return mixed  True or PEAR_Error
     * @access public
     */
    function setContainer(&$form)
    {
        $this->_form =& $form;
        return true;
    }
    
    /**
     * Return the currently used HTML_QuickForm object
     *
     * @return object HTML_QuickForm (reference to) or PEAR_Error
     * @access public
     */
    function &getContainer()
    {
        isset($this->_form) or $this->init();
        return $this->_form;
    }
    
    /**
     * Instantiate the HTML_QuickForm container if needed, and set it up
     * 
     * @access protected
     */
    function init()
    {
        if (!isset($this->_form)) {
            // Try to give the form a unique name using $_requestPrefix
            $this->_form =& new HTML_QuickForm(
                    "{$this->_requestPrefix}DataGridEditForm", 'get');
            $this->_isUserContainer = false;
        } else {
            $this->_isUserContainer = true;
        }
    }

    /**
     * Build form elements
     *
     * @param   array $columns Columns' fields names and labels 
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        $defaults = array();
        foreach ($this->_columns as $i => $spec) {
            $this->_form->addElement('text', $spec['field'], $spec['label']);
            if ($this->_records) {
                $defaults[$spec['field']] = $this->_records[0][$i];
            }
        }
        
        $this->_form->setDefaults($defaults);

        // Only add a submit button and extraVars if the QF container wasn't
        // provided by the user
        if (!$this->_isUserContainer) {
            $this->_form->addElement('submit', null, $this->_options['textSubmit']);
            foreach ($this->_options['extraVars'] as $var => $value) {
                $this->_form->addElement('hidden', $var, $value);
            }
        }
    }

    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        return $this->_form->toHTML();
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
