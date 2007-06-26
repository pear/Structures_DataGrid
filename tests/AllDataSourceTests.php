<?php

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllDataSourceTests::main');
}
 
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
 
require_once 'DataSourceArrayTest.php';
require_once 'DataSourceDBQueryTest.php';
require_once 'DataSourceMDB2Test.php';
require_once 'DataSourcePDOTest.php';
 
class AllDataSourceTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
 
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Structures_DataGrid DataSources');
 
        $suite->addTestSuite('DataSourceArrayTest');
        $suite->addTestSuite('DataSourceDBQueryTest');
        $suite->addTestSuite('DataSourceMDB2Test');
        $suite->addTestSuite('DataSourcePDOTest');
 
        return $suite;
    }
}
 
if (PHPUnit_MAIN_METHOD == 'AllDataSourceTests::main') {
    AllDataSourceTests::main();
}
?>
