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
    define('PHPUnit_MAIN_METHOD', 'DataSourceDataObjectTest::main');
}

require_once 'DataSourceTestCore.php';
require_once 'File/Util.php';
require_once "DB/DataObject.php";

/**
 * DB_DataObject DataSource core tests
 */
class DataSourceDataObjectTest extends DataSourceTestCore
{
    var $dbfile;
    var $strField = 'the_str';
    var $dataobject;

    function getDriverClassName()
    {
        return 'Structures_DataGrid_DataSource_DataObject';
    }

    function setUp()
    {
        parent::setUp();
        if (!isset($this->dbfile)) {
            $this->dbfile = File_Util::tmpDir() . '/sdgtest.db';
            if (file_exists($this->dbfile)) {
                unlink($this->dbfile);
            }
            $db = sqlite_open($this->dbfile);
            sqlite_query($db, 'CREATE TABLE test (num int not null, '.
                        'the_str char(255) not null primary key);'); 
            foreach ($this->data as $row) {
                sqlite_query($db, "INSERT INTO test VALUES ({$row['num']}, ".
                        "'{$row['the_str']}');");
            }
            sqlite_close($db);
            unset($db);
        }

        $options = &PEAR::getStaticProperty("DB_DataObject","options");
        $options["database"] =  "sqlite:///{$this->dbfile}";
        $options["proxy"] = "full";
    }

    function bindDefault()
    {
        $this->datasource->bind(new TestDataObject());
    }

    function testSortProperty()
    {
        // Testing that sort property is taken into account
        $dataobject = new TestDataObject();
        $dataobject->fb_linkOrderFields = array('num');
        $this->datasource->bind($dataobject);
        $this->datasource->fetch();
        $this->assertEquals('ORDER BY num', 
            trim($dataobject->lastQuery['order_by']));
        
        // Testing that sort() overrides sort property (see bug #12942)
        $dataobject = new TestDataObject();
        $dataobject->fb_linkOrderFields = array('the_str');
        $this->datasource->bind($dataobject);
        $this->datasource->sort('the_str');
        $this->datasource->fetch();
        // With bug #12942 the following equaled to 'ORDER BY "the_str", the_str'
        $this->assertEquals('ORDER BY "the_str"', 
            trim($dataobject->lastQuery['order_by']));

        // Testing that sort() overrides sort property when passed an array
        $dataobject = new TestDataObject();
        $dataobject->fb_linkOrderFields = array('the_str');
        $this->datasource->bind($dataobject);
        $this->datasource->sort(array('the_str' => 'ASC'));
        $this->datasource->fetch();
        $this->assertEquals('ORDER BY "the_str" ASC', 
            trim($dataobject->lastQuery['order_by']));
    }

    function testGetter()
    {
        // support camel case getters (see bug #9803)
        $dataobject = new TestDataObjectWithGetter();
        $this->datasource->bind($dataobject);
        $data = $this->datasource->fetch();
        $this->assertEquals('test <- getTheStr()', $data[0]['the_str']);

        // support DB_DataObject half camel case getters (see bug #13199)
        $dataobject = new TestDataObjectWithAltGetter();
        $this->datasource->bind($dataobject);
        $data = $this->datasource->fetch();
        $this->assertEquals('test <- getThe_str()', $data[0]['the_str']);
    }
}

class TestDataObject extends DB_DataObject
{
    var $__table = 'test';
    var $num;
    var $the_str;
    var $lastQuery = null;

    function TestDataObject()
    {
        $this->keys('the_str');
    }

    function find($n = false)
    {
        $this->lastQuery = $this->_query;
        parent::find($n);
    }
}

class TestDataObjectWithAltGetter extends TestDataObject
{
    function getThe_str()
    {
        return $this->the_str . ' <- getThe_str()';
    }
}

class TestDataObjectWithGetter extends TestDataObjectWithAltGetter
{
    function getTheStr()
    {
        return $this->the_str . ' <- getTheStr()';
    }
}

if (PHPUnit_MAIN_METHOD == 'DataSourceDataObjectTest::main') {
    $suite = new PHPUnit_TestSuite('DataSourceDataObjectTest');
    $result = PHPUnit::run($suite);
    echo $result->toString();
}
?>
