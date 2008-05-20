<?php
/**
 * XML DataSource driver
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Andrew Nagy <asnagy@webitecture.org>,
 *                          Olivier Guilyardi <olivier@samalyse.com>,
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
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @category Structures
 * @package  Structures_DataGrid_DataSource_XML
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'Structures/DataGrid/DataSource/Array.php';

/**
 * XML DataSource driver
 *
 * This class is a DataSource driver for XML data. It accepts strings
 * and filenames. An XPath expression can be specified to extract a
 * subset from the given XML data.
 *
 * SUPPORTED OPTIONS:
 * 
 * - xpath:           (string)  XPath to a subset of the XML data.
 * - fieldAttribute:  (string)  Which attribute of the XML source should be used
 *                              as column field name (only used if the XML source
 *                              has attributes).
 * - labelAttribute:  (string)  Which attribute of the XML source should be used
 *                              as column label (only used if 'generate_columns'
 *                              is true and the XML source has attributes).
 *
 * @example  bind-xml1.php  Bind a simple XML string
 * @example  bind-xml2.php  Bind a more complex XML string (using 'xpath' option)
 * @package  Structures_DataGrid_DataSource_XML
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @category Structures
 * @version  $Revision$
 */
class Structures_DataGrid_DataSource_XML extends
    Structures_DataGrid_DataSource_Array
{
    // TODO: use XML_Indexing package for reading (=> streaming support)

    /**
     * Constructor
     * 
     */
    function Structures_DataGrid_DataSource_XML()
    {
        parent::Structures_DataGrid_DataSource_Array();
        $this->_addDefaultOptions(
            array(
                'xpath'          => '',
                'fieldAttribute' => null,
                'labelAttribute' => null
            )
        );
    }

    /**
     * Bind XML data 
     * 
     * @access  public
     * @param   string  $xml        XML data or filename of a XML file
     * @param   array   $options    Options as an associative array
     * @return  mixed               true on success, PEAR_Error on failure 
     */
    function bind($xml, $options = array())
    {
        if ($options) {
            $this->setOptions($options);
        }

        // check whether we have XML data or a filename
        $isFile = false;
        if (strlen($xml) < 256 && @is_file($xml)) {
            $isFile = true;
        }

        // prepare the XML data
        if (version_compare(PHP_VERSION, '5.0.0', '>=')) {
            $xml = $isFile ? simplexml_load_file($xml)
                           : simplexml_load_string($xml);
            if ($xml === false) {
                return PEAR::raiseError('XML couldn\'t be read.');
            }
        } elseif ($isFile) {  // PHP 4 and filename given
            $xml = file_get_contents($xml);
            if ($xml === false) {
                return PEAR::raiseError('XML couldn\'t be read.');
            }
        }

        // extract a subset from the XML data if an XPath is provided
        if ($this->_options['xpath']) {
            $xml = $this->_evaluteXPath($xml);
            if (PEAR::isError($xml)) {
                return $xml;
            }
        }

        // parse XML data
        $xml = $this->_parseXML($xml);
        if (PEAR::isError($xml)) {
            return $xml;
        }

        if ($xml && !$this->_options['fields']) {
            $this->setOption('fields', array_keys($xml[0]));
        }

        return true;
    }

    /**
     * Extract a subset from the XML data
     * 
     * @access  private
     * @param   mixed    $xml         A string or a SimpleXML instance
     * @return  mixed    string, PEAR_Error or SimpleXML instance
     */
    function _evaluteXPath($xml)
    {
        // with PHP 5.2, use SimpleXML
        if (version_compare(PHP_VERSION, '5.2.0', '>=')) { 
            $xml = $xml->xpath($this->_options['xpath']);
            if ($xml === false) {
                return PEAR::raiseError('\'xpath\' option couldn\'t ' .
                                        'be evaluated.');
            }
            return $xml[0];
        } elseif (include_once('XML/XPath.php')) { // check for XML_XPath
            $xpath = new XML_XPath($xml);
            $result =& $xpath->evaluate($this->_options['xpath']); 
            if (PEAR::isError($result)) {
                return $result;
            }
            $xml = '';
            while ($result->next()) {
                $xml .= $result->toString(null, false, false);
            }
            return $xml; 
        } else {
            return PEAR::raiseError('\'xpath\' option cannot be used ' .
            'because neither the XML_XPath package nor PHP >= 5.2.0 ' .
            'is installed.');
        }
    }

    /**
     * Parse data from an XML string or a SimpleXML instance
     * 
     * @access  private
     * @param   mixed    $xml         A string or a SimpleXML instance
     * @return  mixed    array with parsed data or PEAR_Error
     */
    function _parseXML($xml)
    {
        // PHP 5 handling
        if (version_compare(PHP_VERSION, '5.0.0', '>=')) {
            foreach ($xml as $tmprow) {
                $row = array();
                foreach ($tmprow->attributes() as $key => $value) {
                    // use 'fieldAttribute' as item key, if 'fieldAttribute'
                    // option set
                    if (!is_null($this->_options['fieldAttribute'])) {
                        foreach ($value->attributes() as $a => $b) {
                            if ($this->_options['fieldAttribute'] == $a) {
                                $key = (string)$b;
                            }
                        }
                    }
                    $row['attributes' . $key] = $value;
                }
                foreach ((array)$tmprow as $key => $value) {
                    if ($key === '@attributes') {
                        continue;
                    }
                    // use 'fieldAttribute' as item key, if 'fieldAttribute'
                    // option set
                    if (!is_null($this->_options['fieldAttribute'])) {
                        foreach ($tmprow->attributes() as $a => $b) {
                            if ($this->_options['fieldAttribute'] == $a) {
                                $key = (string)$b;
                            }
                        }
                    }
                    $row[$key] = $value;
                }
                $this->_ar[] = $this->_processRowSimpleXML($row);
            }
            return $this->_ar;
        }

        // PHP 4 handling follows

        // check XML_(Un)serializer installation
        if (!include_once('XML/Unserializer.php')) {
            return PEAR::raiseError('XML_Serializer package not found');
        }

        // instantiate XML_Unserializer object
        $unserializer =& new XML_Unserializer();
        $unserializer->setOption('parseAttributes', true);
        // set containers for attributes and content
        // (for the case that attributes are found)
        $unserializer->setOption('attributesArray', 'attributes');
        $unserializer->setOption('contentName', '_content');
        // use 'fieldAttribute' as item key, if 'fieldAttribute'
        // option set
        if (!is_null($this->_options['fieldAttribute'])) {
            $unserializer->setOption('keyAttribute',
                                     $this->_options['fieldAttribute']);
        }
        // unserialize the XML data
        $test = $unserializer->unserialize($xml, false);
        if (PEAR::isError($test)) {
            return $test;
        }
        // fetch the unserialized data
        $data = $unserializer->getUnserializedData();
        // build a simple array
        list($junk, $data) = each($data);  // TODO: check $data here
        // check the array, can it be parsed?
        if (!is_array($data)) { // FIXME: fails with 1 row of data
            return PEAR::raiseError('Unable to bind the XML data. ' .
                                    'You may want to set the ' .
                                    '\'xpath\' option.');
        }
        // parse the given XML data
        foreach ($data as $index => $row) {
            if (!is_array($row) || !is_numeric($index)) {
                return PEAR::raiseError('Unable to bind the XML data. ' .
                                        'You may want to set the ' .
                                        '\'xpath\' option.');
            }
            $this->_ar[] = $this->_processRow($row);
        }
        return $this->_ar;
    }

    /**
     * Process XML row
     * 
     * @access  private
     * @param   array    $row         Row from unserializer data array
     * @param   string   $keyPrefix   Prepended to key, for recursive processing
     * @return  array    of form: array($field1 => $value1, $field2 => $value2, ...)
     */
    function _processRow($row, $keyPrefix = '')
    {
        $rowProcessed = array();
        foreach ($row as $item => $info) {
            $itemKey = $keyPrefix . $item;
            switch (true) {
                // item has no attributes and unique tag name
                case !is_array($info):
                    // $itemKey needs to be replaced in this case to get the
                    // right field name
                    if (   !is_null($this->_options['fieldAttribute'])
                        && isset($row['attributes'][$this->_options['fieldAttribute']])) {
                        $itemKey = $row['attributes'][$this->_options['fieldAttribute']];
                    }
                    // if $item is '_content' here, save attribute's value as
                    // the label for this column
                    if (   !$this->_options['labels']
                        && !is_null($this->_options['labelAttribute'])
                        && $item == '_content') {
                        if (isset($row['attributes'][$this->_options['labelAttribute']])) {
                            $labels[$itemKey] = $row['attributes'][$this->_options['labelAttribute']];
                        } else {
                            $labels[$itemKey] = $itemKey;
                        }
                    }
                    $rowProcessed[$itemKey] = $info;
                    break;
                // items with non-unique tag names, or 'fieldAttribute'
                // option is null; process array elements recursively as
                // separate items
                case !isset($info['attributes']):
                    $rowProcessed += $this->_processRow($info, $itemKey);
                    break;
                // attributes found: field attribute is already in item key;
                // extract label if option set and 'labels' option is empty 
                case    !$this->_options['labels']
                     && !is_null($this->_options['labelAttribute']):
                    if (isset($info['attributes'][$this->_options['labelAttribute']])) {
                        $labels[$itemKey] = $info['attributes'][$this->_options['labelAttribute']];
                    } else {
                        $labels[$itemKey] = $itemKey;
                    }
                    // no break here; we need the content!
                default:
                    $rowProcessed[$itemKey] = 
                        isset($info['_content']) ? $info['_content'] : ''; 
            }
        }
        // set labels if extracted
        if (!$this->_options['labels'] && isset($labels)) {
            $this->setOption('labels', $labels);
        }
        return $rowProcessed;
    }

    /**
     * Process XML row from SimpleXML
     * 
     * @access  private
     * @param   array    $row         Row from SimpleXML
     * @param   string   $keyPrefix   Prepended to key, for recursive processing
     * @return  array    of form: array($field1 => $value1, $field2 => $value2, ...)
     */
    function _processRowSimpleXML($row, $keyPrefix = '')
    {
        $rowProcessed = array();
        foreach ($row as $item => $info) {
            $itemKey = $keyPrefix . $item;
            // extract label if option set and $this->_options['labels']
            // is empty 
            if (   !$this->_options['labels']
                && !is_null($this->_options['labelAttribute'])
               ) {
                $key = $value = $item;
                if (   substr($item, 0, 10) != 'attributes'
                    && array_key_exists('attributes' . $this->_options['labelAttribute'], $row)
                   ) {
                    $value = $row['attributes' . $this->_options['labelAttribute']];
                }
                $labels[$key] = $value;
            }
            // save the content
            if (is_a($info, 'SimpleXMLElement') && $info->children()) {
                $rowProcessed += $this->_processRowSimpleXML($info, $itemKey);
            } else {
                $rowProcessed[$itemKey] = (string)$info;
            }
        }
        // set labels if extracted
        if (!$this->_options['labels'] && isset($labels)) {
            $this->setOption('labels', $labels);
        }
        return $rowProcessed;
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
