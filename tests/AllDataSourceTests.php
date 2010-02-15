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
    define('PHPUnit_MAIN_METHOD', 'AllDataSourceTests::main');
}

require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'DataSourceArrayTest.php';
require_once 'DataSourceDBQueryTest.php';
require_once 'DataSourceCSVTest.php';
require_once 'DataSourceMDB2Test.php';
require_once 'DataSourceXMLTest.php';
require_once 'DataSourceDataObjectTest.php';
require_once 'DataSourcePDOTest.php';

/**
 * Test all datasources
 */
class AllDataSourceTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Structures_DataGrid Tests');
        $suite->addTestSuite('DataSourceArrayTest');
        $suite->addTestSuite('DataSourceDBQueryTest');
        $suite->addTestSuite('DataSourceCSVTest');
        $suite->addTestSuite('DataSourceMDB2Test');
        $suite->addTestSuite('DataSourceXMLTest');
        $suite->addTestSuite('DataSourceDataObjectTest');

        // PHP5 only:
        if (version_compare(phpversion(), '5', '>=')) {
            $suite->addTestSuite('DataSourcePDOTest');
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllDataSourceTests::main') {
    AllDataSourceTests::main();
}
?>
