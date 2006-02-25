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
require_once 'Smarty/Smarty.class.php';

/**
 * Structures_DataGrid_Renderer_Smarty Class
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_Smarty extends Structures_DataGrid_Renderer_Common
{
// FIXME: test this renderer
// FIXME: implement paging feature request (write a common paging class for
//        HTMLTable and Smarty renderer)

    /**
     * Name of the template file
     *
     * @var string
     * @access private
     */
    var $_tpl;

    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_Smarty()
    {
        parent::Structures_DataGrid_Renderer_Common();
        $this->_addDefaultOptions(
            array(
                // FIXME: maybe add an option for the template filename
            )
        );
    }

    /**
     * Initialize the Smarty instance if it is not already existing
     * 
     * @access protected
     */
    function init()
    {
        if (is_null($this->_container)) {
            $this->_container = new Smarty();
            $this->_container->template_dir = dirname($_SERVER['SCRIPT_FILENAME']);
            $this->_container->compile_dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/compile';
        }
    }

    /**
     * Attach a Smarty instance
     * (deprecated, use $renderer->setContainer() or $dg->fill() instead)
     *
     * @param object Smarty instance
     * @access public
     */
    function setSmarty(&$smarty)
    {
        $this->_container = &$smarty;
    }

    /**
     * Set the Smarty template
     *
     * @param string  Filename of the template
     * @access public
     */
    function setTemplate($tpl)
    {
        // FIXME: make $_tpl an option (?)
        if (is_file($this->_container->template_dir . '/' . $tpl)) {
            $this->_tpl = $tpl;
        } else {
            return new PEAR_Error('Error: Unable to find ' .
                                  $this->_container->template_dir . '/' . $tpl);
        }
    }

    /**
     * Handles building the body of the table
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        if ($this->_tpl != '') {
            $this->_container->assign('recordSet',   $this->_records);
            $this->_container->assign('columnSet',   $this->_columns);
            $this->_container->assign('recordLimit', $this->_pageLimit);
            $this->_container->assign('currentPage', $this->_page);
        }
    }

    /**
     * Gets the Smarty object
     *
     * OBSOLETE
     * 
     * @access  public
     * @return  object Console_Table   The Console_Table object for the DataGrid
     */
    function getSmarty()
    {
        return $this->_container;
    }

    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        $smarty = $this->getSmarty();

        // FIXME: this error shouldn't occur
        // FIXME: test $this->_tpl here (if it's not set, display() may result
        //        in an error [to be checked!])
        if (PEAR::isError($smarty)) {
            return $smarty;
        } else {
            $smarty->display($this->_tpl);
        }
    }

}

?>
