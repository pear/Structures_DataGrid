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
// | Authors: Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Mark Wiesemann <wiesemann@php.net>                          |
// |          Andrew Nagy <asnagy@webitecture.org>                        |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'Structures/DataGrid/Renderer.php';
require_once 'Pager/Pager.php';

/**
 * Pager rendering driver
 *
 * This driver provide generic paging.
 * 
 * RECOGNIZED OPTIONS:
 *
 * - pagerOptions (array)   Options passed to Pager::factory().
 *                          These are ignored if you provide a custom Pager
 *                          via the fill() or setContainer() methods.
 *                          Basic defaults are: mode: Sliding, delta:5, 
 *                          separator: "|", prevImg: "<<", nextImg: ">>".
 *                          The extraVars and excludeVars options are 
 *                          populated according to the Renderer common 
 *                          extraVars and excludeVars options. You may also
 *                          specify some variables to be added or excluded
 *                          here.
 *                          The totalItems, perPage, urlVar, and currentPage 
 *                          options are set accordingly to the data statistics
 *                          reported by the DataGrid and DataSource. You may 
 *                          overload these values here if you know what your 
 *                          are doing.
 *                          
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_Pager extends Structures_DataGrid_Renderer
{
    /**
     * Rendering container
     * @var object Pager object
     * @access protected
     */
    var $_pager;
   
    /**
     * Constructor
     *
     * Set default options values
     *
     * @access  public
     */
    function Structures_DataGrid_Renderer_Pager()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'pagerOptions' => array(
                    'mode'        => 'Sliding',
                    'delta'       => 5,
                    'separator'   => '|',
                    'prevImg'     => '<<',
                    'nextImg'     => '>>',
                    'totalItems'  => null, // dynamic ; see init()
                    'perPage'     => null, // dynamic ; see init()
                    'urlVar'      => null, // dynamic ; see init()
                    'currentPage' => null, // dynamic ; see init()
                    'extraVars'   => array(),
                    'excludeVars' => array(),
                ),
            )
        );
    }
    
    /**
     * Attach an already instantiated Pager object
     *
     * @var     object  Pager object
     * @return  mixed   True or PEAR_Error
     * @access public
     */
    function setContainer(&$pager)
    {
        $this->_pager =& $pager;
        return true;
    }
    
    /**
     * Return the currently used Pager object
     *
     * @return object Pager (reference to) or PEAR_Error
     * @access public
     */
    function &getContainer()
    {
        isset($this->_pager) or $this->init();
        return $this->_pager;
    }
    
    /**
     * Instantiate the Pager container if needed, and set it up
     * 
     * @access protected
     */
    function init()
    {
        if (!isset($this->_pager)) {
            $options = $this->_options['pagerOptions'];
           
            if (is_null($options['totalItems'])) {
                $options['totalItems'] = $this->_totalRecordsNum;
            }
            
            if (is_null ($options['perPage'])) {
                $options['perPage'] = is_null($this->_pageLimit) 
                                    ? $this->_totalRecordsNum 
                                    : $this->_pageLimit;
            }
            
            if (is_null ($options['urlVar'])) {
                $options['urlVar'] = $this->_requestPrefix . 'page';
            }
            
            if (is_null ($options['currentPage'])) {
                $options['currentPage'] = $this->_page;
            }
            
            $options['excludeVars'] = array_merge($this->_options['excludeVars'],
                                                  $options['excludeVars']);    
            
            $options['extraVars'] = array_merge($this->_options['extraVars'],
                                                $options['extraVars']);    
            
            $this->_pager =& Pager::factory($options);
        }
    }
    
    /**
     * Retrieve links from the Pager object
     *
     * @return string HTML links
     * @access protected
     */
    function flatten()
    {
        return $this->_pager->links;
    }

    /**
     * Helper methods for drivers that automatically load this driver
     *
     * This is (or has been...) used by the HTMLTable and Smarty driver
     * 
     * @param object $renderer External driver
     * @param array  $pagerOptions pager options
     * @return void
     * @access public
     */
    function setupAs(&$renderer,$pagerOptions)
    {
        $this->setLimit($renderer->_page, $renderer->_pageLimit, 
                        $renderer->_totalRecordsNum);
        $this->setRequestPrefix($renderer->_requestPrefix);
        $options['pagerOptions'] = array_merge($this->_options['pagerOptions'], 
                                               $pagerOptions);
        $options['excludeVars'] = $renderer->_options['excludeVars'];
        $options['extraVars'] = $renderer->_options['extraVars'];
        $this->setOptions($options);
    }
}

