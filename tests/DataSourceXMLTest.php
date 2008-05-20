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
    define('PHPUnit_MAIN_METHOD', 'DataSourceXMLTest::main');
}

require_once 'DataSourceTestCore.php';
require_once 'File/Util.php';

/**
 * Structures_DataGrid_DataSource_XML tests
 */
class DataSourceXMLTest extends DataSourceTestCore
{
    // No space in tag names
    var $strField = 'thestr';

// TODO: test 'xpath' option
// TODO: test both input from string and input from file

    var $xmlFile;

    function getDriverClassName()
    {
        return 'Structures_DataGrid_DataSource_XML';
    }

    function setUp()
    {
        parent::setUp();
        if (!isset($this->xmlFile)) {
            $this->xmlFile = File_Util::tmpDir() . '/sdgtest.xml';
            if (file_exists($this->xmlFile)) {
                unlink($this->xmlFile);
            }
            $this->writeXmlFile($this->xmlFile, $this->data);
        }
    }

    function buildXmlString($data)
    {
        $xml = "<records>\n";
        foreach ($data as $row) {
            $xml .= "  <record>\n";
            foreach ($row as $tag => $value) {
                $xml .= "    <$tag>$value</$tag>\n";
            }
            $xml .= "  </record>\n";
        }
        $xml .= "</records>\n";
        return $xml;
    }

    function buildComplexXmlString($data)
    {
        $xml = <<<XML
<result>
  <dummy1>1</dummy1>
  <dummy2>2</dummy2>
  <dummy3>3</dummy3>
  <records>

XML;
        foreach ($data as $row) {
            $xml .= "    <record>\n";
            foreach ($row as $tag => $value) {
                $xml .= "      <$tag>$value</$tag>\n";
            }
            $xml .= "    </record>\n";
        }
        $xml .= <<<XML
  </records>
</result>
XML;
        return $xml;
    }

    function writeXmlFile($filename, $data)
    {
        $fp = fopen($filename, 'w');
        $xml = $this->buildXmlString($data);
        fwrite($fp, $xml);
        fclose($fp);
    }

    function writeComplexXmlFile($filename, $data)
    {
        $fp = fopen($filename, 'w');
        $xml = $this->buildComplexXmlString($data);
        fwrite($fp, $xml);
        fclose($fp);
    }

    function bindDefault()
    {
        $this->datasource->bind($this->xmlFile);
    }

    function testSort()
    {
        $this->bindDefault();
        $expected = array(
            array('num' => '3', 'thestr' => ''),
            array('num' => '2', 'thestr' => 'viel spaß'),
            array('num' => '1', 'thestr' => 'présent'),
            array('num' => '1', 'thestr' => 'test'),
        );
        $this->datasource->sort('num', 'DESC');
        $this->assertEquals($expected, $this->datasource->fetch());
    }

    function testComplexFetchAll()
    {
        $filename = File_Util::tmpDir() . '/sdgtest.complex.xml';
        $this->writeComplexXmlFile($filename, $this->data);
        $this->datasource->bind($filename, array('xpath' => '/result/records'));
        $this->assertEquals($this->data, $this->datasource->fetch());
    }

    function testCountAfterFetchFromString()
    {
        $this->datasource->bind($this->buildXmlString($this->data));
        $this->assertEquals($this->data, $this->datasource->fetch());
        $this->assertEquals(count($this->data), $this->datasource->count());
    }

    function testSortFromComplexString()
    {
        $this->datasource->bind($this->buildComplexXmlString($this->data),
                                array('xpath' => '/result/records'));
        $expected = array(
            array('num' => '1', 'thestr' => 'présent'),
            array('num' => '1', 'thestr' => 'test'),
            array('num' => '2', 'thestr' => 'viel spaß'),
            array('num' => '3', 'thestr' => ''),
        );
        $this->datasource->sort('num', 'ASC');
        $this->assertEquals($expected, $this->datasource->fetch());
    }

    function testAttributeToColumn()
    {
        // see bug #13840
        $xml = '<rows><row acctname="x" adgroup="foo" />'.
            '<row acctname="x" adgroup="bar" /></rows>'; 
        $this->datasource->setOption('generate_columns', true);
        $this->datasource->bind($xml);
        $columns = $this->datasource->getColumns();
        $this->assertEquals(2, count($columns));
        if (count($columns) == 2) {
            $this->assertEquals('attributesacctname', $columns[0]->getLabel());
            $this->assertEquals('attributesacctname', $columns[0]->getField());
            $this->assertEquals('attributesadgroup', $columns[1]->getLabel());
            $this->assertEquals('attributesadgroup', $columns[1]->getField());
        } 

        $content = array(array('attributesacctname' => 'x', 'attributesadgroup' => 'foo'),
                array('attributesacctname' => 'x', 'attributesadgroup' => 'bar'));
        $this->assertEquals($content, $this->datasource->fetch());                
    }

    function testFieldAndLabelAttributes()
    {
        $xml = '<data><item name="foo" label="bar">test</item>'.
            '<item name="foo" label="bar">test2</item></data>';
        $this->datasource->setOption('generate_columns', true);
        $this->datasource->setOption('fieldAttribute', 'name');
        $this->datasource->setOption('labelAttribute', 'label');
        $this->datasource->bind($xml);
        $columns = $this->datasource->getColumns();
        $this->assertEquals(3, count($columns));
        if (count($columns) == 3) {
            $this->assertEquals('attributesname', $columns[0]->getLabel());
            $this->assertEquals('attributesname', $columns[0]->getField());
            $this->assertEquals('attributeslabel', $columns[1]->getLabel());
            $this->assertEquals('attributeslabel', $columns[1]->getField());
            $this->assertEquals('bar', $columns[2]->getLabel());
            $this->assertEquals('foo', $columns[2]->getField());
        }
    }

    function testDeeperNesting()
    {
        $xml = <<<XML
<data>
  <row>
    <col0>Test0</col0>
    <col1><x><a>Test1</a></x></col1>
    <col2><y><b><c>Test2</c></b></y></col2>
    <col3><z>Test3</z></col3>
    <col4>Test4</col4>
  </row>
  <row>
    <col0>Test0</col0>
    <col1><x><a>Test1</a></x></col1>
    <col2><y><b><c>Test2</c></b></y></col2>
    <col3><z>Test3</z></col3>
    <col4>Test4</col4>
  </row>
</data>
XML;
        $this->datasource->setOption('generate_columns', true);
        $this->datasource->bind($xml);
        $columns = $this->datasource->getColumns();
        $this->assertEquals(5, count($columns));
        if (count($columns) == 5) {
            $this->assertEquals('col0',    $columns[0]->getLabel());
            $this->assertEquals('col0',    $columns[0]->getField());
            $this->assertEquals('col1xa',  $columns[1]->getLabel());
            $this->assertEquals('col1xa',  $columns[1]->getField());
            $this->assertEquals('col2ybc', $columns[2]->getLabel());
            $this->assertEquals('col2ybc', $columns[2]->getField());
            $this->assertEquals('col3z',   $columns[3]->getLabel());
            $this->assertEquals('col3z',   $columns[3]->getField());
            $this->assertEquals('col4',    $columns[4]->getLabel());
            $this->assertEquals('col4',    $columns[4]->getField());
        }
    }

}

if (PHPUnit_MAIN_METHOD == 'DataSourceXMLTest::main') {
    $suite = new PHPUnit_TestSuite('DataSourceXMLTest');
    $result = PHPUnit::run($suite);
    echo $result->toString();
}
?>
