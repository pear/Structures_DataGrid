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
    define('PHPUnit_MAIN_METHOD', 'EncodingTest::main');
}

require_once 'TestCore.php';
require_once 'Structures/DataGrid/Renderer/CSV.php';
require_once 'Structures/DataGrid/Column.php';

error_reporting(E_ALL);

/**
 * Encoding/Charsets support tests
 */
class EncodingTest extends TestCore
{

    public function setUp() {
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped("This test requires mb");
        }
        parent::setUp();
    }

    /**
     * Test BOM and encoding conversion from the CSV renderer
     */
    function testUnicodeCSV()
    {
        $renderer = new Structures_DataGrid_Renderer_CSV();
        $renderer->setOption('encoding', 'utf-8');
        $renderer->setOption('delimiter', ";");
        $renderer->setOption('lineBreak', "\r\n");

        $data = array(
            array('A' => 'e', 'B' => 'é'),
            array('A' => 'a', 'B' => 'à'));
        $columns = array(
            new Structures_DataGrid_Column('A', 'A'),
            new Structures_DataGrid_Column('B', 'B'));
        $renderer->setColumns($columns);

        // Test strings made with Excel, vim and hexdump:
        $utf8 =
            "\xef\xbb\xbf\x41\x3b\x42\x0d\x0a\x65\x3b\xc3\xa9\x0d\x0a\x61\x3b".
            "\xc3\xa0\x0d\x0a";
        $utf16le =
            "\xff\xfe\x41\x00\x3b\x00\x42\x00\x0d\x00\x0a\x00\x65\x00\x3b\x00".
            "\xe9\x00\x0d\x00\x0a\x00\x61\x00\x3b\x00\xe0\x00\x0d\x00\x0a\x00";
        $utf16be =
            "\xfe\xff\x00\x41\x00\x3b\x00\x42\x00\x0d\x00\x0a\x00\x65\x00\x3b".
            "\x00\xe9\x00\x0d\x00\x0a\x00\x61\x00\x3b\x00\xe0\x00\x0d\x00\x0a";
        $utf32le =
            "\xff\xfe\x00\x00\x41\x00\x00\x00\x3b\x00\x00\x00\x42\x00\x00\x00".
            "\x0d\x00\x00\x00\x0a\x00\x00\x00\x65\x00\x00\x00\x3b\x00\x00\x00".
            "\xe9\x00\x00\x00\x0d\x00\x00\x00\x0a\x00\x00\x00\x61\x00\x00\x00".
            "\x3b\x00\x00\x00\xe0\x00\x00\x00\x0d\x00\x00\x00\x0a\x00\x00\x00";
        $utf32be =
            "\x00\x00\xfe\xff\x00\x00\x00\x41\x00\x00\x00\x3b\x00\x00\x00\x42".
            "\x00\x00\x00\x0d\x00\x00\x00\x0a\x00\x00\x00\x65\x00\x00\x00\x3b".
            "\x00\x00\x00\xe9\x00\x00\x00\x0d\x00\x00\x00\x0a\x00\x00\x00\x61".
            "\x00\x00\x00\x3b\x00\x00\x00\xe0\x00\x00\x00\x0d\x00\x00\x00\x0a";

        $this->assertEquals($utf8, $this->_buildCSV($renderer, $data, 'utf-8'));
        $this->assertEquals($utf16le, $this->_buildCSV($renderer, $data, 'utf-16le'));
        $this->assertEquals($utf16be, $this->_buildCSV($renderer, $data, 'utf-16be'));
        $this->assertEquals($utf32le, $this->_buildCSV($renderer, $data, 'utf-32le'));
        $this->assertEquals($utf32be, $this->_buildCSV($renderer, $data, 'utf-32be'));

        // Checking that the BOM is present when header is turned off
        $renderer->setOption('buildHeader', false);
        $this->assertEquals("\xef\xbb\xbfe;é\r\na;à\r\n",
                $this->_buildCSV($renderer, $data, 'utf-8'));
    }

    function _buildCSV($renderer, $data, $targetEncoding)
    {
        $renderer = clone($renderer);
        $renderer->init();
        $renderer->setOption('targetEncoding', $targetEncoding);
        $renderer->build($data, 0, true);
        return $renderer->getOutput();
    }

}

if (PHPUnit_MAIN_METHOD == 'EncodingTest::main') {
    $suite = new PHPUnit_TestSuite('EncodingTest');
    $result = PHPUnit::run($suite);
    echo $result->toString();
}
?>
