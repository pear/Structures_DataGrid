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

require_once 'Structures/DataGrid/Renderer/HTMLTable.php';
// require_once 'Smarty/Smarty.class.php';

/**
 * Structures_DataGrid_Renderer_Smarty Class
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_Smarty extends Structures_DataGrid_Renderer_HTMLTable
{
// FIXME: test this renderer
// FIXME: implement paging feature request (write a common paging class for
//        HTMLTable and Smarty renderer)

    /**
     * Smarty container
     * @var object $_smarty;
     */
    var $_smarty;
    
    /**
     * Attach an already instantiated Smarty object
     * 
     * @param  object $smarty Smarty container
     * @return mixed True or PEAR_Error
     */
    function setContainer(&$smarty)
    {
        $this->_smarty =& $smarty;
        return true;
    }
    
    /**
     * Return the currently used Smarty object
     * @return object Smarty or PEAR_Error object
     */
    function &getContainer()
    {
        isset($this->_smarty) or $this->init();
        return &$this->_smarty;
    }
    
    /**
     * Initialize the Smarty instance if it is not already existing
     * 
     * @access protected
     */
    function init()
    {
        if (!isset($this->_smarty)) {
            $this->_smarty = new Smarty();
        }
    }

    /**
     * Attach a Smarty instance
     * 
     * @deprecated Use setContainer() instead
     * @param object Smarty instance
     * @access public
     */
    function setSmarty(&$smarty)
    {
        return $this->setContainer($smarty);
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
            $this->_smarty->assign('recordSet',   $this->_records);
            $this->_smarty->assign('columnSet',   $this->_columns);
            $this->_smarty->assign('recordLimit', $this->_pageLimit);
            $this->_smarty->assign('currentPage', $this->_page);
        }
    }

    /**
     * Gets the Smarty object
     *
     * @deprecated Use getContainer() instead 
     * @access  public
     * @return  object Smarty container (reference)
     */
    function &getSmarty()
    {
        return $this->getContainer();
    }


    /**
     * Discard the unsupported render() method
     * 
     * This Smarty driver does not support the render() method.
     * It is required to use the setContainer() (or 
     * Structures_DataGrid::fill()) method in order to do anything
     * with this driver.
     * 
     */
    function render()
    {
        return $this->_noSupport(__FUNCTION__);
    }
}

?>
