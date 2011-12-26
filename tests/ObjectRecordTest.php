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
require_once 'Structures/DataGrid.php';

/**
 * Object record support test
 *
 * This class tests that records which are objects instead of associative 
 * arrays are properly handled by the datagrid core and by renderers.
 *
 * There is two rendering scenarios:
 *
 * If the renderer has the "objectPreserving" feature (such as Smarty) then
 * the object records must be "rendered" untouched and references preserved.
 *
 * If the renderer can't "render" objects (such as CSV, HTMLTable, etc..) then
 * it must handle them mostly as if they were associative arrays, except that
 * the formatter must receive the objects untouched, so that it can call some
 * of their methods, etc...
 */
class ObjectRecordTest extends TestCore
{
    function testObjectPreservingWithSmarty()
    {
        $pear = new ObjectRecordTest_Store('pear');
        $doctrine = new ObjectRecordTest_Store('doctrine');
        $propel = new ObjectRecordTest_Store('propel');

        $datagrid = new Structures_DataGrid(2);
        $datagrid->bind(array(&$pear, &$doctrine, &$propel));

        $output = $datagrid->getOutput('Smarty');
        $this->assertEquals('pear', $output['recordSet'][0]->name);
        $this->assertEquals('pear(tm)', $output['recordSet'][0]->getBrand());
        $pear->name = 'pear2';
        $this->assertEquals('pear2', $output['recordSet'][0]->name);
    }

    function testObjectFormattingWithCSV()
    {
        $pear = new ObjectRecordTest_Store('pear');
        $doctrine = new ObjectRecordTest_Store('doctrine');
        $propel = new ObjectRecordTest_Store('propel');

        $datagrid = new Structures_DataGrid();
        $datagrid->bind(array(&$pear, &$doctrine, &$propel));

        $column = new Structures_DataGrid_Column('brand', 'brand');
        $column->setFormatter(array($this, '_testCSVBrandFormatter'));
        $datagrid->addColumn(new Structures_DataGrid_Column('name', 'name'));
        $datagrid->addColumn($column);

        $output = explode("\n", $datagrid->getOutput('CSV'));
        $this->assertEquals('name,brand', $output[0]);
        $this->assertEquals('pear,pear(tm)', $output[1]);
        $this->assertEquals('doctrine,doctrine(tm)', $output[2]);
        $this->assertEquals('propel,propel(tm)', $output[3]);
    }

    function _testCSVBrandFormatter($data)
    {
        return $data['record']->getBrand();
    }
}

class ObjectRecordTest_Store
{
    var $name;

    function ObjectRecordTest_Store($name)
    {
        $this->name = $name;
    }

    function getBrand()
    {
        return "{$this->name}(tm)";
    }
}

