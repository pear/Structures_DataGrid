<?php
/**
 * XUL Rendering Driver
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
 * @package  Structures_DataGrid_Renderer_XUL
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'XML/Util.php';

/**
 * XUL Rendering Driver
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: no
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 * 
 * This renderer class will render a XUL listbox.
 * For additional information on the XUL Listbox, refer to this url:
 * http://www.xulplanet.com/references/elemref/ref_listbox.html
 *
 * You have to setup your XUL document, just as you would with an HTML
 * document. This driver will only generated the <listbox> element and
 * content.
 * 
 * GENERAL NOTES:
 * 
 * Basic example: 
 * <code>
 * <?php 
 * header('Content-type: application/vnd.mozilla.xul+xml'); 
 * 
 * echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
 * echo "<?xml-stylesheet href=\"myStyle.css\" type=\"text/css\"?>\n";
 * 
 * echo "<window title=\"MyDataGrid\" 
 *        xmlns=\"http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul\">\n";
 *        
 * // Instantiate your datagrid and setup its datasource, then call:
 * $datagrid->setRenderer(DATAGRID_RENDER_XUL);
 * $datagrid->render();
 * 
 * echo "</window>\n";
 * ?> 
 * </code>
 * 
 * SUPPORTED OPTIONS:
 *
 * - selfPath:      (string) The complete path for sorting and paging links
 *                           (default: $_SERVER['PHP_SELF'])
 *
 * @version     $Revision$
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
                'selfPath' => $_SERVER['PHP_SELF']
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
                    // No current sort on this column. Next click will ascend. We
                    // show no arrow.
                    $dirArg = 'ASC';
                    $dirCur = 'natural';
                }

                $onCommand = 
                    "oncommand=\"location.href='{$this->_options['selfPath']}?" 
                    . $this->_buildSortingHttpQuery($field, $dirArg, true)
                    . "'\"";
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
