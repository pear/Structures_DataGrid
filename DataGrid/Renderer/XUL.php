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
//require_once 'XML/XUL.php';
require_once 'XML/Util.php';

/**
 * Structures_DataGrid_Renderer_XUL Class
 *
 * This renderer class will render an XUL listbox.
 * For additional information on the XUL Listbox, refer to this url:
 * http://www.xulplanet.com/references/elemref/ref_listbox.html
 *
 * Recognized options:
 *
 * - title:     (string) The title of the datagrid
 *                       (default: 'DataGrid')
 * - css:       (array)  An array of css URL's
 *                       (default: 'chrome://global/skin/')
 * - selfPath:  (string) The complete path for sorting and paging links
 *                       (default: $_SERVER['PHP_SELF'])
 *
 * @version     $Revision$
 * @author      Andrew S. Nagy <asnagy@webitecture.org>
 * @author      Olivier Guilyardi <olivier@samalyse.com>
 * @author      Mark Wiesemann <wiesemann@php.net>
 * @access      public
 * @package     Structures_DataGrid
 * @category    Structures
 * @todo        Implement PEAR::XML_XUL upon maturity
 */
class Structures_DataGrid_Renderer_XUL extends Structures_DataGrid_Renderer_Common
{

    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XUL()
    {
        parent::Structures_DataGrid_Renderer_Common();
        $this->_addDefaultOptions(
            array(
                'title'    => 'DataGrid',
                'css'      => array('chrome://global/skin/'),
                'selfPath' => $_SERVER['PHP_SELF']
            )
        );
    }

    /**
     * Initialize a string for the XUL XML code if it is not already existing
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
     * Sets the datagrid title
     *
     * @access  public
     * @param   string      $title      The title of the datagrid
     */
    function setTitle($title)
    {
        $this->_options['title'] = $title;
    }
    
    /**
     * Adds a stylesheet to the list of stylesheets
     *
     * @access  public
     * @param   string      $url        The url of the stylesheet
     */
    function addStyleSheet($url)
    {
        array_push($this->_options['css'], $url);
    }

    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        // Define XML
        $xul = XML_Util::getXMLDeclaration() . "\n";
        
        // Define Stylesheets
        foreach ($this->_options['css'] as $css) {
            $xul .= "<?xml-stylesheet href=\"$css\" type=\"text/css\"?>\n";
        }
        
        // Define Window Element
        $xul .= "<window title=\"{$this->_options['title']}\" " . 
                "xmlns=\"http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul\">\n";

        // Define Listbox Element
        $xul .= "<listbox rows=\"" . $this->_pageLimit . "\">\n";

        // FIXME: move header building into buildHeader()
        // => Problem: Where have the above XML tags (and the related closing
        //             tags) to go? (flatten()?)

        // Build Grid Header
        $xul .= "  <listhead>\n";
        for ($col = 0; $col < $this->_columnsNum; $col++) {
            $field = $this->_columns[$col]['field'];
            $label = $this->_columns[$col]['label'];

            if ($this->_currentSortField == $field) {
                if ($this->_currentSortDirection == 'ASC') {
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
                // No current sort on this column. Next click will ascend. We
                // show no arrow.
                $dirArg = 'ASC';
                $dirCur = 'natural';
            }

            // FIXME: clicking has no effect (at least in Firefox 1.5)
            //        (old code from SDG 0.6.3 has the same problem)
            $onClick = "location.href='" . $this->_options['selfPath'] . 
                       '?' . $this->_requestPrefix . 'orderBy=' . $field .
                       "&amp;" . $this->_requestPrefix . "direction=$dirArg';";
            $label = XML_Util::replaceEntities($label);
            $xul .= '    <listheader label="' . $label . '" ' . 
                    "sortDirection=\"$dirCur\" onCommand=\"$onClick\" />\n";
        }
        $xul .= "  </listhead>\n";

        // Build Grid Body
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $xul .= "  <listitem>\n";
            for ($col = 0; $col < $this->_columnsNum; $col++) {
                $value = $this->_records[$row][$col];

                // FIXME: 'ä' is displayed as '?' ==> encoding is required!
                $xul .= '    ' .
                        XML_Util::createTag('listcell',
                                            array('label' => $value)) . "\n";
            }

            $xul .= "  </listitem>\n";
        }
        $xul .= "</listbox>\n";
        $xul .= "</window>\n";

        $this->_container .= $xul;
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
        header('Content-type: application/vnd.mozilla.xul+xml');
        return $this->_container;
    }

}

?>
