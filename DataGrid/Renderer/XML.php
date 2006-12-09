<?php
/**
 * XML Rendering Driver
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
 * @category Structures
 * @package  Structures_DataGrid_Renderer_XML
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
 *                           must be given if this optio is set to true.
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: no
 * - Output Buffering:  yes
 * - Direct Rendering:  yes
 * - Streaming:         yes
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
            $this->_fp = fopen($this->_options['filename'], 'wb');
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
