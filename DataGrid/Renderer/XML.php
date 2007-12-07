<?php
/**
 * XML Rendering Driver
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
 * @category Structures
 * @package  Structures_DataGrid_Renderer_XML
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'XML/Util.php';

/**
 * XML Rendering Driver
 *
 * SUPPORTED OPTIONS:
 *
 * - useXMLDecl:    (bool)   Whether the XML declaration string should be added
 *                           to the output. The encoding attribute value will 
 *                           get set from the common "encoding" option. If you 
 *                           need to further customize the XML declaration 
 *                           (version, etc..), then please set "useXMLDecl" to
 *                           false, and add your own declaration string.
 * - outerTag:      (string) The name of the tag for the datagrid, without 
 *                           brackets
 * - rowTag:        (string) The name of the tag for each row, without brackets
 * - fieldTag:      (string) The name of the tag for each field inside a row, 
 *                           without brackets. The special value '{field}' is 
 *                           replaced by the field name.
 * - fieldAttribute:(string) The name of the attribute for the field name.
 *                           null stands for no attribute 
 * - labelAttribute:(string) The name of the attribute for the column label.
 *                           null stands for no attribute 
 * - filename:      (string) Filename of the generated XML file; boolean false
 *                           means that no filename will be sent
 * - saveToFile:   (boolean) Whether the output should be saved on the local
 *                           filesystem. Please note that the 'filename' option
 *                           must be given if this option is set to true.
 * - writeMode:     (string) The mode that is used in the internal fopen() calls.
 *                           Useful e.g. when you want to append to existing file.
 *                           C.p. the fopen() documentation for the allowed modes.
 * - onMove:          (-)    IGNORED
 * - onMoveData:      (-)    IGNORED
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: no
 * - Output Buffering:  yes
 * - Direct Rendering:  yes
 * - Streaming:         yes
 * - Object Preserving: no
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @category Structures
 * @package  Structures_DataGrid_Renderer_XML
 */
class Structures_DataGrid_Renderer_XML extends Structures_DataGrid_Renderer
{

    /**
     * XML output
     * @var string
     * @access private
     */
    var $_xml;

    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XML()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'useXMLDecl'        => true,
                'outerTag'          => 'DataGrid',
                'rowTag'            => 'Row',
                'fieldTag'          => '{field}',
                'fieldAttribute'    => null,
                'labelAttribute'    => null,
                'filename'          => false,
                'saveToFile'        => false,
                'writeMode'         => 'wb',
            )
        );
        $this->_setFeatures(
            array(
                'streaming' => true, 
                'outputBuffering' => true,
            )
        );
    }

    /**
     * Initialize a string for the XML code if it is not already existing
     * 
     * @access protected
     */
    function init()
    {
        $this->_xml = '';
        if ($this->_options['saveToFile'] === true) {
            if ($this->_options['filename'] === false) {
                return PEAR::raiseError('No filename specified via "filename" ' .
                                        'option.');
            }
            $this->_fp = fopen($this->_options['filename'],
                               $this->_options['writeMode']);
            if ($this->_fp === false) {
                return PEAR::raiseError('Could not open file "' .
                                        $this->_options['filename'] . '" ' .
                                        'for writing.');
            }
        }

        $xml = '';
        if ($this->_options['useXMLDecl']) {
            $xml .= XML_Util::getXMLDeclaration('1.0',
                    $this->_options['encoding']) . "\n";
        }
        $xml .= "<{$this->_options['outerTag']}>\n";
        if ($this->_options['saveToFile'] === true) {
            $res = fwrite($this->_fp, $xml);
            if ($res === false) {
                return PEAR::raiseError('Could not write into file "' .
                                        $this->_options['filename'] . '".');
            }
        } elseif ($this->_streamingEnabled) {
            echo $xml;
        } else {
            $this->_xml .= $xml;
        }
    }

    /**
     * Generates the XML for the DataGrid
     *
     * @access  public
     * @return  string      The XML of the DataGrid
     */
    function toXML()
    {
        return $this->getOutput();
    }

    /**
     * Build a body row
     *
     * @param   int   $index Row index (zero-based)
     * @param   array $data  Record data. 
     * @access  protected
     * @return  void
     */
    function buildRow($index, $data)
    {
        $xml = "  <{$this->_options['rowTag']}>\n";
        foreach ($data as $col => $value) {
            $field = $this->_columns[$col]['field'];
            $tag = ($this->_options['fieldTag'] == '{field}') 
                   ? $field : $this->_options['fieldTag'];

            $attributes = array();
            if (!is_null($this->_options['fieldAttribute'])) {
                $attributes[$this->_options['fieldAttribute']] 
                    = $this->_columns[$col]['field'];
            }
            if (!is_null($this->_options['labelAttribute'])) {
                $attributes[$this->_options['labelAttribute']] 
                    = $this->_columns[$col]['label'];
            }

            if (isset($this->_options['columnAttributes'][$field])) {
                $attributes = array_merge (
                                $this->_options['columnAttributes'][$field], 
                                $attributes);
            }

            $xml .= '    ' . XML_Util::createTag($tag, $attributes, $value) . "\n";
        }
        $xml .= "  </{$this->_options['rowTag']}>\n";
        if ($this->_options['saveToFile'] === true) {
            $res = fwrite($this->_fp, $xml);
            if ($res === false) {
                return PEAR::raiseError('Could not write into file "' .
                                        $this->_options['filename'] . '".');
            }
        } elseif ($this->_streamingEnabled) {
            echo $xml;
        } else {
            $this->_xml .= $xml;
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
        return $this->_xml;
    }

    /**
     * Finish building the datagrid.
     *
     * @access  protected
     * @return  void
     */
    function finalize()
    {
        $xml = "</{$this->_options['outerTag']}>\n";
        if ($this->_options['saveToFile'] === true) {
            $res = fwrite($this->_fp, $xml);
            if ($res === false) {
                return PEAR::raiseError('Could not write into file "' .
                                        $this->_options['filename'] . '".');
            }
            $res = fclose($this->_fp);
            if ($res === false) {
                return PEAR::raiseError('Could not close file "' .
                                        $this->_options['filename'] . '".');
            }
        } elseif ($this->_streamingEnabled) {
            echo $xml;
        } else {
            $this->_xml .= $xml;
        }
    }

    /**
     * Render to the standard output
     *
     * @access  public
     */
    function render()
    {
        if ($this->_options['saveToFile'] === false) {
            header('Content-type: text/xml');
            if ($this->_options['filename'] !== false) {
                header('Content-disposition: attachment; filename=' .
                       $this->_options['filename']);
            }
        }
        parent::render();
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
