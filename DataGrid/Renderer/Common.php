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
// +----------------------------------------------------------------------+
//
// $Id$

/**
 * Structures_DataGrid_Renderer Class
 *
 * Base class of all Renderer drivers
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_Common 
{
    var $currentSortField     = null;
    var $currentSortDirection = null;
   
    var $columns = array();
    var $records = array();
    var $nColumns;
    var $nRecords;
    var $nRecordsTotal;
    var $limit = null;
    var $offset = 0;
    var $output = null;
    
    function init ()
    {
    }
    
    function buildHeader () 
    {
    }
   
    function buildBody ()
    {
    }
    
    function buildFooter () 
    {
    }

    function finalize ()
    {
    }
    
    function generate ()
    {
        if ($this->records)
        {
            if (is_null ($this->limit)) {
                $this->limit = count ($this->records);
            }
            
            for ($i = $this->offset; $i < $this->limit; $i++) {
                $content = array ();
                foreach ($this->columns as $column) {
                    $content[] = $column->recordToValue ($this->record[$i]);
                }
                $this->records[$i - $this->offset] = $content;
            }

            $ii = count ($this->records);
            for ($i = $this->limit - $this->offset; $i < $ii; $i++) {
                unset ($this->records[$i]);
            }

        }
       
        $this->nColumns = count ($this->columns);
        $this->nRecords = count ($this->records);
        
        $this->init ();
        
        $this->buildHeader ();
       
        $this->buildBody ();
        
        $this->buildFooter ();

        $this->finalize ();
    }

    function getOutput ()
    {
        if (is_null ($this->output)) {
            $this->generate ();
        }
        return $this->output;
    }

    function render()
    {
        echo $this->getOutput ();
    }
    
    /**
     * Sets the rendered status.  This can be used to "flush the cache" in case
     * you need to render the datagrid twice with the second time having changes
     *
     * This is quite an obsolete method...
     * 
     * @access  public
     * @params  bool        $status     The rendered status of the DataGrid
     */
    function setRendered($status = false)
    {
        if (!$status) {
            $this->output = null;
        }
        /* What are we supposed to do with $status = true ? */
    }   
        
}

?>
