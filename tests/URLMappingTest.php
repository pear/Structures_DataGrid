<?php
/**
 * Unit Tests for Structures_DataGrid
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Olivier Guilyardi <olivier@samalyse.com>,
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
 * CVS file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'URLMappingTest::main');
}

require_once 'TestCore.php';
require_once 'Structures/DataGrid.php';
require_once 'Structures/DataGrid/DataSource.php';


error_reporting(E_ALL);

/**
 * URL Mapping Tests
 */
class URLMappingTest extends TestCore
{
    var $datagrid;

    function testSimple() {
        $this->setURL("/page/5/foo/DESC");
        $datagrid =& new Structures_DataGrid(10);
        $datagrid->setUrlFormat("/page/:page/:orderBy/:direction");
        $this->assertEquals(5, $datagrid->getCurrentPage());
        $datasource = new URLMappingTest_MockDataSource();
        $datagrid->bindDataSource($datasource);
        $this->assertEquals(array('foo' => 'DESC'), $datasource->sortSpec);
    }

    function testMissingSort()
    {
        $this->setURL("/page/5");
        $datagrid =& new Structures_DataGrid(10);
        $datagrid->setUrlFormat("/page/:page/:orderBy/:direction");
        $this->assertEquals(5, $datagrid->getCurrentPage());
    }

    function testPrefix()
    {
        $this->setURL("/page/5");
        $datagrid =& new Structures_DataGrid(10);
        $datagrid->setUrlFormat("/:page", "/page");
        $this->assertEquals(5, $datagrid->getCurrentPage());
    }

    function testPostInstantiation()
    {
        $datagrid =& new Structures_DataGrid(10);
        $this->setURL("/page/5");
        $datagrid->setUrlFormat("/page/:page");
        $this->assertEquals(5, $datagrid->getCurrentPage());
    }

    function setURL($url) {
        $_SERVER['REQUEST_URI'] = $url;
    }

    function testPager()
    {
        // Setting datagrid up
        $this->setURL("/");
        $datagrid =& new Structures_DataGrid(10);
        $datagrid->setDefaultSort(array('foo' => 'ASC'));
        $datagrid->setUrlFormat("/page/:page/:orderBy/:direction");
        $datasource = new URLMappingTest_MockDataSource();
        $datasource->fakeCount = 50;
        $datagrid->bindDataSource($datasource);

        // Retrieving paging HTML
        $links = $datagrid->getOutput('Pager');
        $this->assertFalse(empty($links));

        // Building XML object to parse href urls
        $links = str_replace('&nbsp;', '', $links);
        $xml = new SimpleXMLElement("<pager>$links</pager>");
        $tags = $xml->xpath('//a');
        $this->assertEquals(5, count($tags));
        $urls = array();
        foreach ($tags as $link) {
            $urls[] = (string) $link['href'];
        }

        // Testing urls
        $this->assertEquals("/page/2/foo/ASC", $urls[0]);
        $this->assertEquals("/page/3/foo/ASC", $urls[1]);
        $this->assertEquals("/page/4/foo/ASC", $urls[2]);
        $this->assertEquals("/page/5/foo/ASC", $urls[3]);
        $this->assertEquals("/page/2/foo/ASC", $urls[4]);
    }
    
}

class URLMappingTest_MockDataSource extends Structures_DataGrid_DataSource
{
    var $sortSpec;
    var $fakeCount = 0;

    function URLMappingTest_MockDataSource()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_setFeatures(array('multiSort' => true));
    }

    function count() {
        return $this->fakeCount;
    }

    function fetch($offset = 0, $len = null) {
        $data = array();
        $ii = $this->fakeCount - $offset;
        $ii = $ii > $len ? $len : $ii;
        for ($i = 0; $i < $ii; $i++) {
            $data[] = array('foo' => 'bar');
        }
        return array();
    }

    function sort($spec) {
        $this->sortSpec = $spec;
    }
}

if (PHPUnit_MAIN_METHOD == 'URLMappingTest::main') {
    $suite = new PHPUnit_TestSuite('URLMappingTest');
    $result = PHPUnit::run($suite);
    echo $result->toString();
}

?>
