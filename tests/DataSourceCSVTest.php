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

require_once 'DataSourceTest.php';

/**
 * Structures_DataGrid_DataSource_CSV tests
 */
class DataSourceCSVTest extends DataSourceTest
{
    protected $complexInput = array(
        'aaa,bbb',
        'aaa,"bbb"',
        '"aaa","bbb"',
        'aaa,bbb',
        '"aaa",bbb',
        '"aaa",   "bbb"',
        ',',
        'aaa,',
        ',"aaa"',
        '"",""',
        '"\\"","aaa"',
        '"""""",',
        '""""",aaa',
        '"\\""",aaa',
        'aaa,"\\"bbb,ccc',
        'aaa,bbb   ',
        'aaa,"bbb   "',
        'aaa"aaa","bbb"bbb',
        'aaa"aaa""",bbb',
        'aaa"\\"a","bbb"'
    );

    protected $complexOutput= array ( 
        array ('aaa', 'bbb',),
        array ('aaa', 'bbb',),
        array ('aaa', 'bbb',),
        array ('aaa', 'bbb',),
        array ('aaa', 'bbb',),
        array ('aaa', 'bbb',),
        array ('', '',),
        array ('aaa', '',),
        array ('', 'aaa',),
        array ('', '',),
        array ('\\"', 'aaa',),
        array ('""', '',),
        array ("\"\",aaa\n",),
        array ("\\\"\",aaa\n",),
        array ('aaa', "\\\"bbb,ccc\n",),
        array ('aaa', 'bbb   ',),
        array ('aaa', 'bbb   ',),
        array ('aaa"aaa"', 'bbbbbb',),
        array ('aaa"aaa"""', 'bbb',),
        array ('aaa"\\"a"', 'bbb',),
    );

    protected $csvFile;

    protected function getDriverClassName()
    {
        return 'Structures_DataGrid_DataSource_CSV';
    }

    public function setUp()
    {
        parent::setUp();
        if (!isset($this->csvFile)) {
            $this->csvFile = "/tmp/sdgtest.csv";
            if (file_exists($this->csvFile)) {
                unlink($this->csvFile);
            }
            $header = array_keys($this->data[0]);
            $this->writeCsvFile($this->csvFile, $this->data, $header);
        }
    }

    public function writeCsvFile($filename, $data, $header = null)
    {
        $fp = fopen($filename, 'w');
        if (!is_null($header)) {
            fputcsv($fp, $header);
        }
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    public function bindDefault()
    {
        $this->datasource->bind($this->csvFile, array('header' => true));
    }

    public function testComplexStrings()
    {
        $result = array();
        foreach ($this->complexInput as $line) {
            $datasource = new Structures_DataGrid_DataSource_CSV();
            $datasource->bind($line . "\n");
            $data = $datasource->fetch();
            $result[] = @$data[0];
        }
        $this->assertEquals($this->complexOutput, $result);
    }

    public function testComplexFile()
    {
        $filename = '/tmp/sdgtest.complex.csv';
        $result = array();
        foreach ($this->complexInput as $line) {
            file_put_contents($filename, "$line\n");
            $datasource = new Structures_DataGrid_DataSource_CSV();
            $datasource->bind($filename);
            $data = $datasource->fetch();
            $result[] = @$data[0];
        }
        $this->assertEquals($this->complexOutput, $result);
    }

}

?>
