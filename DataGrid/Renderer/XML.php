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

require_once 'Structures/DataGrid/Renderer/Common.php';
require_once 'XML/Util.php';

/**
 * Structures_DataGrid_Renderer_XML Class
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_XML extends Structures_DataGrid_Renderer_Common
{

    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XML()
    {
        parent::Structures_DataGrid_Renderer_Common();
    }

    /**
     * Initialize a string for the XML code if it is not already existing
     * 
     * @access protected
     */
    function init()
    {
        if (is_null($this->_container)) {
            $this->_container = '';
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
     * Handles building the body of the DataGrid
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        $xml = XML_Util::getXMLDeclaration() . "\n";

        $xml .= "<DataGrid>\n";
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $xml .= "  <Row>\n";
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $value = $this->_records[$row][$col];
                $field = $this->_columns[$col]['field'];

                $xml .= '    ' . XML_Util::createTag($field, null, $value) . "\n";
            }
            $xml .= "  </Row>\n";
        }
        $xml .= "</DataGrid>\n";

        $this->_container .= $xml;
    }

    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        return $this->_container;
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
