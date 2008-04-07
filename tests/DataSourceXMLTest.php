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

}

?>
