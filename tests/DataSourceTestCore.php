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


require_once 'TestCore.php';
require_once 'Structures/DataGrid/DataSource.php';

error_reporting(E_ALL);

/**
 * DataSource core tests
 */
class DataSourceTestCore extends TestCore
{
    var $datasource;
    var $numField = 'num';
    var $strField = 'the str';
    var $data = array();

    function setUp()
    {
        parent::setUp();
        $class = $this->getDriverClassName();
        $file = str_replace('_', '/', $class) . '.php';
        if (!$fp = @fopen($file, 'r', true)) {
            $this->fail("Skipping: Driver unavailable: $class");
        }
        fclose($fp);
        require_once($file);
        $this->datasource = new $class();

        $this->data = array(
            array($this->numField => '1', $this->strField => 'test'),
            array($this->numField => '1', $this->strField => 'présent'),
            array($this->numField => '2', $this->strField => 'viel spaß'),
            array($this->numField => '3', $this->strField => ''),
        );
    }

    function tearDown()
    {
        $this->datasource->free();
        unset($this->datasource);
    }

    function testFetchAll()
    {
        $this->bindDefault();
        $this->assertEquals($this->data, $this->datasource->fetch());
    }

    function testLimit()
    {
        $this->bindDefault();
        $records = $this->datasource->fetch(1);
        $expected = array_slice($this->data, 1);
        $this->assertEquals($expected, $records);
        $this->bindDefault();
        $records = $this->datasource->fetch(1,1);
        $this->assertEquals($this->data[1], $records[0]);
    }

    function testCountBeforeFetch()
    {
        $this->bindDefault();
        $this->assertEquals(count($this->data), $this->datasource->count());
        $this->assertEquals($this->data, $this->datasource->fetch());
    }

    function testCountAfterFetch()
    {
        $this->bindDefault();
        $this->assertEquals($this->data, $this->datasource->fetch());
        $this->assertEquals(count($this->data), $this->datasource->count());
    }

    function testSort()
    {
        $this->bindDefault();
        $expected = array(
            array($this->numField => '3', $this->strField => ''),
            array($this->numField => '2', $this->strField => 'viel spaß'),
            array($this->numField => '1', $this->strField => 'présent'),
            array($this->numField => '1', $this->strField => 'test'),
        );
        $this->datasource->sort($this->numField, 'DESC');
        $this->assertEquals($expected, $this->datasource->fetch());
    }

    function testMultiSort()
    {
        $this->bindDefault();
        if ($this->datasource->hasFeature('multiSort')) {
            $expected = array(
                array($this->numField => '3', $this->strField => ''),
                array($this->numField => '2', $this->strField => 'viel spaß'),
                array($this->numField => '1', $this->strField => 'test'),
                array($this->numField => '1', $this->strField => 'présent'),
            );
            $this->datasource->sort(array($this->numField => 'DESC', 
                $this->strField => 'DESC'));
            $this->assertEquals($expected, $this->datasource->fetch());
        } else {
            $this->fail("Skipping: Driver does not support multiSort");
        }
    }
}

?>
