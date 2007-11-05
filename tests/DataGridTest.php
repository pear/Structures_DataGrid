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
    define('PHPUnit_MAIN_METHOD', 'DataGridTest::main');
}

require_once 'TestCore.php';
require_once 'Structures/DataGrid.php';
require_once 'Structures/DataGrid/Renderer.php';

error_reporting(E_ALL);

/**
 * Tests the driver-less, core routines
 */
class DataGridTest extends TestCore
{
    function testDefaultSortPassing()
    {
        // Setting the default sort before attaching the renderer
        $datagrid =& new Structures_DataGrid();
        $datagrid->setDefaultSort(array('date' => 'ASC'));
        $datagrid->bind(array(array('date' => '2007')));
        $renderer =& new DataGridTest_MockRenderer();
        $datagrid->attachRenderer($renderer);
        $datagrid->render();
        $this->assertEquals(array('date' => 'ASC'), $renderer->getSort());

        // Setting the default sort after attaching the renderer
        unset($datagrid);
        unset($renderer);
        $datagrid =& new Structures_DataGrid();
        $renderer =& new DataGridTest_MockRenderer();
        $datagrid->attachRenderer($renderer);
        $datagrid->setDefaultSort(array('date' => 'ASC'));
        $datagrid->bind(array(array('date' => '2007')));
        $datagrid->render();
        $this->assertEquals(array('date' => 'ASC'), $renderer->getSort());
    }
}

class DataGridTest_MockRenderer extends Structures_DataGrid_Renderer
{
    function getSort()
    {
        return $this->_currentSort;
    }
}

if (PHPUnit_MAIN_METHOD == 'DataGridTest::main') {
    $suite = new PHPUnit_TestSuite('DataGridTest');
    $result =& PHPUnit::run($suite);
    echo $result->toString();
}
?>
