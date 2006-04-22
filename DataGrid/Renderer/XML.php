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
// | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
// |          Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Mark Wiesemann <wiesemann@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'Structures/DataGrid/Renderer.php';
require_once 'XML/Util.php';

/**
 * XML Rendering Driver
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 *
 * SUPPORTED OPTIONS:
 *
 * - useXMLDecl: (bool)   Whether the XML declaration string should be added
 *                        to the output
 *                        (default: true)
 * - outerTag:   (string) The name of the tag for the datagrid (without brackets)
 *                        (default: 'DataGrid')
 * - rowTag:     (string) The name of the tag for each row (without brackets)
 *                        (default: 'Row')
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support : no
 * - Output Buffering  : yes
 * - Direct Rendering  : no
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
                'useXMLDecl' => true,
                'outerTag'   => 'DataGrid',
                'rowTag'     => 'Row'
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
     * Handles building the body of the DataGrid
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        if ($this->_options['useXMLDecl']) {
            $this->_xml .= XML_Util::getXMLDeclaration() . "\n";
        }

        $this->_xml .= "<{$this->_options['outerTag']}>\n";
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $this->buildRow($row, $this->_records[$row]);
        }
        $this->_xml .= "</{$this->_options['outerTag']}>\n";
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
        $this->_xml .= "  <{$this->_options['rowTag']}>\n";
        foreach ($data as $col => $value) {
            $field = $this->_columns[$col]['field'];

            $this->_xml .= '    ' . XML_Util::createTag($field, null, $value) . "\n";
        }
        $this->_xml .= "  </{$this->_options['rowTag']}>\n";
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
     * Render to the standard output
     *
     * @access  public
     */
    function render()
    {
        header('Content-type: text/xml');
        parent::render();
    }
}

?>
