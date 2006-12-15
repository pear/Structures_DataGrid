<?php
/**
 * RSS data source driver
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2006, Andrew Nagy <asnagy@webitecture.org>,
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
 * @package  Structures_DataGrid_DataSource_RSS
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/DataSource/Array.php';
require_once 'XML/RSS.php';

/**
 * RSS data source driver
 *
 * @version     $Revision$
 * @author      Andrew Nagy <asnagy@webitecture.org>
 * @access      public
 * @package     Structures_DataGrid_DataSource_RSS
 * @category    Structures
 */
class Structures_DataGrid_DataSource_RSS extends
    Structures_DataGrid_DataSource_Array
{
    /**
     * Constructor
     * 
     */
    function Structures_DataGrid_DataSource_RSS()
    {
        parent::Structures_DataGrid_DataSource_Array();
    }

    /**
     * Bind RSS data 
     * 
     * @access  public
     * @param   string $file        RSS file
     * @param   array $options      Options as an associative array
     * @return  mixed               true on success, PEAR_Error on failure 
     */
    function bind($file, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        $rss = new XML_RSS($file);
        $result = $rss->parse();
        if (PEAR::isError($result)) { 
            return $result;
        }
        
        $this->_ar = $rss->getItems();
        
        return true;
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
