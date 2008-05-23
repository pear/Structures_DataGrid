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


require_once 'PEAR.php';
require_once 'PHPUnit.php';
require_once 'Text/Diff.php';
require_once 'Text/Diff/Renderer/unified.php';

error_reporting(E_ALL);

/**
 * DataSource core tests
 */
class TestCore extends PHPUnit_TestCase
{
    var $lastPearError = null;
    var $catchPearError = false;

    function setUp()
    {
        error_reporting(E_ALL);
        $this->catchPearError = false;
        PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, array(&$this, 'onPearError'));
        $this->setLooselyTyped(true);
    }

    function onPearError($error)
    {
        if ($this->catchPearError) {
            $this->lastPearError =& $error;
        } else {
        print_r($error->getType());
            $this->fail(
                "------------------------\n".
                "PEAR Error: " . $error->toString() . "\n" .
                "------------------------\n");
        }            
    }

    function suppressPhpWarnings()
    {
        error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
    }

    function assertEquals($expected, $actual, $message = '', $delta = 0) {
        if ((is_array($actual)  && is_array($expected)) ||
            (is_object($actual) && is_object($expected))) {
            if (is_array($actual) && is_array($expected)) {
                ksort($actual);
                ksort($expected);
            }

            if ($this->_looselyTyped) {
                $actual   = $this->_convertToString($actual);
                $expected = $this->_convertToString($expected);
            }

            $orig_actual = $actual;
            $orig_expected = $expected;
            $actual   = serialize($actual);
            $expected = serialize($expected);

            if ($actual !== $expected) {

                $oldIni = ini_get('html_errors');
                if($oldIni != 0) {
                    ini_set('html_errors',0);
                }

                ob_start();
                echo " --------- EXPECTED -----------\n";
                var_dump($orig_expected);
                $expected = explode("\n", ob_get_contents());
                ob_clean();
                echo " ---------- ACTUAL ------------\n";
                var_dump($orig_actual);
                $actual = explode("\n", ob_get_contents());
                ob_end_clean();

                if($oldIni != 0) {
                    ini_set('html_errors',$oldIni);
                }
  
                $diff = new Text_Diff($expected, $actual);
                $renderer = &new Text_Diff_Renderer_unified();
                $diff = $renderer->render($diff);
                $message = sprintf(
                  "%sdiff:\n%s",

                  !empty($message) ? $message . ' ' : '',
                  $diff
                );
                return $this->fail($message);
            }
        }

        elseif (is_numeric($actual) && is_numeric($expected)) {
            $message = sprintf(
              '%sexpected %s%s, actual %s',

              !empty($message) ? $message . ' ' : '',
              $expected,
              ($delta != 0) ? ('+/- ' . $delta) : '',
              $actual
            );

            if (!($actual >= ($expected - $delta) && $actual <= ($expected + $delta))) {
                return $this->fail($message);
            }
        }

        else {
            $message = sprintf(
              '%sexpected %s, actual %s',

              !empty($message) ? $message . ' ' : '',
              $expected,
              $actual
            );

            if ($actual !== $expected) {
                return $this->fail($message);
            }
        }
    }

    function assertContains($needle, $haystack, $message = '') {
        if (is_string($needle) && is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                $this->fail("'$haystack' does not contain '$needle'");
            }
        }

        else if (is_array($haystack) && !is_object($needle)) {
            if (!in_array($needle, $haystack)) {
                $this->fail("Array does not contain '$needle'");
            }
        }

        else {
            $this->fail('Unsupported parameter passed to assertContains().');
        }
    }
}


/**
 * Replace clone()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/language.oop5.cloning
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision$
 * @since       PHP 5.0.0
 * @require     PHP 4.0.0 (user_error)
 */
if (version_compare(phpversion(), '5.0') === -1) {
    // Needs to be wrapped in eval as clone is a keyword in PHP5
    eval('
        function clone($object)
        {
            // Sanity check
            if (!is_object($object)) {
                user_error(\'clone() __clone method called on non-object\', E_USER_WARNING);
                return;
            }
    
            // Use serialize/unserialize trick to deep copy the object
            $object = unserialize(serialize($object));

            // If there is a __clone method call it on the "new" class
            if (method_exists($object, \'__clone\')) {
                $object->__clone();
            }
            
            return $object;
        }
    ');
}

?>
