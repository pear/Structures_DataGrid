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
    define('PHPUnit_MAIN_METHOD', 'RendererSmartyTest::main');
}

require_once 'TestCore.php';
require_once 'Structures/DataGrid/Column.php';
require_once 'Structures/DataGrid/Renderer/Smarty.php';

/**
 * Smarty renderer test
 *
 */
class RendererSmartyTest extends TestCore
{

    function testNumericIndices()
    {
        $renderer = new Structures_DataGrid_Renderer_Smarty();
        $columns = array(
            new Structures_DataGrid_Column('field1', 'field1'),
            new Structures_DataGrid_Column('field2', 'field2'),
        );                
        $renderer->setColumns($columns);
        $data = array(array('field1' => 'val1', 'field2' => 'val2'),
            array('field1' => 'val3', 'field2' => 'val4'));

        $renderer->build($data, 0, true);

        $output = $renderer->getOutput();
        $this->assertEquals('field1', $output['columnSet'][0]['name']);
        $this->assertEquals('field2', $output['columnSet'][1]['name']);
        $this->assertEquals('val1', $output['recordSet'][0][0]);
        $this->assertEquals('val2', $output['recordSet'][0][1]);
        $this->assertEquals('val3', $output['recordSet'][1][0]);
        $this->assertEquals('val4', $output['recordSet'][1][1]);
    }

    function testAssociativeIndices()
    {
        $renderer = new Structures_DataGrid_Renderer_Smarty();
        $columns = array(
            new Structures_DataGrid_Column('field1', 'field1'),
            new Structures_DataGrid_Column('field2', 'field2'),
        );                
        $renderer->setColumns($columns);
        $data = array(array('field1' => 'val1', 'field2' => 'val2'),
            array('field1' => 'val3', 'field2' => 'val4'));

        $renderer->setOption('associative', true);
        $renderer->build($data, 0, true);

        $output = $renderer->getOutput();
        $this->assertEquals('field1', $output['columnSet']['field1']['name']);
        $this->assertEquals('field2', $output['columnSet']['field2']['name']);
        $this->assertEquals('val1', $output['recordSet'][0]['field1']);
        $this->assertEquals('val2', $output['recordSet'][0]['field2']);
        $this->assertEquals('val3', $output['recordSet'][1]['field1']);
        $this->assertEquals('val4', $output['recordSet'][1]['field2']);
    }

    function testPagesNum()
    {
        $renderer = new Structures_DataGrid_Renderer_Smarty();
        $renderer->setLimit(1, 2, 3);
        $renderer->build(array(), 0, true);
        $output = $renderer->getOutput();
        $this->assertEquals(2, $output['pagesNum']);
    }
}


if (PHPUnit_MAIN_METHOD == 'RendererSmartyTest::main') {
    $suite = new PHPUnit_TestSuite('RendererSmartyTest');
    $result = PHPUnit::run($suite);
    echo $result->toString();
}
?>
