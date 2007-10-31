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
    define('PHPUnit_MAIN_METHOD', 'DataSourceArrayTest::main');
}

require_once 'DataSourceTestCore.php';

/**
 * Structures_DataGrid_DataSource_Array tests
 */
class DataSourceArrayTest extends DataSourceTestCore
{
    function getDriverClassName()
    {
        return 'Structures_DataGrid_DataSource_Array';
    }

    function bindDefault()
    {
        $this->datasource->bind($this->data);
    }

    /**
     * Object record support
     *
     * This method tests whether the record set can be an array of objects 
     * (instead of a 2D array), and if these objects are properly sorted, 
     * fetched, and references preserved.
     */
    function testObjectRecord()
    {
        $pear = new DataSourceArrayTestStore('pear');
        $doctrine = new DataSourceArrayTestStore('doctrine');
        $propel = new DataSourceArrayTestStore('propel');
        $this->datasource->bind(array(&$pear, &$doctrine, &$propel));

        $this->assertEquals(3, $this->datasource->count());

        $records = $this->datasource->fetch();
        $this->assertEquals('pear', $records[0]->name);
        $this->assertEquals('pear(tm)', $records[0]->getBrand());
        $this->assertEquals('doctrine', $records[1]->name);
        $this->assertEquals('doctrine(tm)', $records[1]->getBrand());
        $this->assertEquals('propel', $records[2]->name);
        $this->assertEquals('propel(tm)', $records[2]->getBrand());

        $this->datasource->sort('name');
        $records = $this->datasource->fetch(1, 1);
        $this->assertEquals('pear', $records[0]->name);
        $this->assertEquals('pear(tm)', $records[0]->getBrand());

        $records[0]->name = 'pear2';
        $this->assertEquals('pear2', $pear->name);
    }
}

class DataSourceArrayTestStore
{
    var $name;

    function DataSourceArrayTestStore($name)
    {
        $this->name = $name;
    }

    function getBrand()
    {
        return "{$this->name}(tm)";
    }
}

if (PHPUnit_MAIN_METHOD == 'DataSourceArrayTest::main') {
    $suite = new PHPUnit_TestSuite('DataSourceArrayTest');
    $result = PHPUnit::run($suite);
    echo $result->toString();
}

?>
