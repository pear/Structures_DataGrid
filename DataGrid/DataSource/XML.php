<?php
/**
 * XML data source driver
 * 
 * <pre>
 * +----------------------------------------------------------------------+
 * | PHP version 4                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2005 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | This source file is subject to version 2.0 of the PHP license,       |
 * | that is bundled with this package in the file LICENSE, and is        |
 * | available through the world-wide-web at                              |
 * | http://www.php.net/license/2_02.txt.                                 |
 * | If you did not receive a copy of the PHP license and are unable to   |
 * | obtain it through the world-wide-web, please send a note to          |
 * | license@php.net so we can mail you a copy immediately.               |
 * +----------------------------------------------------------------------+
 * | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
 * |          Olivier Guilyardi <olivier@samalyse.com>                    |
 * |          Mark Wiesemann <wiesemann@php.net>                          |
 * +----------------------------------------------------------------------+
 * </pre>
 *
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @category Structures
 * @package Structures_DataGrid_DataSource_XML
 */

require_once 'Structures/DataGrid/DataSource/Array.php';
require_once 'XML/Unserializer.php';

/**
 * XML data source driver
 *
 * This driver accepts the following options:
 *
 * SUPPORTED OPTIONS:
 * 
 * - xpath:           (string)  XPath to a subset of the XML data.
 *                              (default: '')
 * - fieldAttribute:  (string)  Which attribute of the XML source should be used
 *                              as column field name (only used if the XML source
 *                              has attributes).
 *                              (default: null)
 * - labelAttribute:  (string)  Which attribute of the XML source should be used
 *                              as column label (only used if 'generate_columns'
 *                              is true and the XML source has attributes).
 *                              (default: null)
 *
 * @package Structures_DataGrid_DataSource_XML
 * @author Olivier Guilyardi <olivier@samalyse.com>
 * @category Structures
 * @version  $Revision $
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
     * @param   string  $xml        XML data
     * @param   array   $options    Options as an associative array
     * @return  mixed               true on success, PEAR_Error on failure 
     */
    function bind($xml, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        // Extract a subset from the XML data if an XPath is provided:
        if ($this->_options['xpath']) {
            include_once 'XML/XPath.php';
            $xpath = new XML_XPath($xml);
            $result =& $xpath->evaluate($this->_options['xpath']); 
            if (PEAR::isError($result)) {
                return $result;
            }
            $xml = '';
            while ($result->next()) {
                $xml .= $result->toString(null, false, false);
            }   
            
        }
       
        // Instantiate XML_Unserializer Object
        $unserializer = &new XML_Unserializer();
        $unserializer->setOption('parseAttributes', true);
        // Set containers for attributes and content (for the case attributes are found)
        $unserializer->setOption('attributesArray', 'attributes');
        $unserializer->setOption('contentName', 'content');
        // Use fieldAttribute as item key, if fieldAttribute option set
        if (!is_null($this->_options['fieldAttribute'])) {
            $unserializer->setOption('keyAttribute', $this->_options['fieldAttribute']);
        }
        
        // Unserialize the XML Data
        $test = $unserializer->unserialize($xml, false);
        if (PEAR::isError($test)) {
            return $test;
        }
        
        // Fetch the unserialized Data
        $data = $unserializer->getUnserializedData();

        // Build a simple array:
        list($junk, $data) = each($data);
        foreach ($data as $index => $row) {
            if (!is_array($row) or !is_numeric($index)) {
                return PEAR::raiseError('Unable to bind the xml data. '.
                                        'You may want to set the \'xpath\' option.');
            }
            $this->_ar[] = $this->_processRow($row);
        }

        if ($this->_ar and !$this->_options['fields']) {
            $this->setOption('fields', array_keys($this->_ar[0]));
        }
        
        return true;
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
                // Item has no attributes and unique tag name
                case !is_array($info):
                    $rowProcessed[$itemKey] = $info;
                    break;
                // Items with non-unique tag names, or fieldAttribute option is null
                // Process array elements recursively as separate items
                case !isset($info['attributes']):
                    $rowProcessed += $this->_processRow($info, $itemKey);
                    break;
                // Attributes found: field attribute is already in item key
                // extract label if option set and $this->_options['labels'] is empty 
                case !$this->_options['labels'] && 
                    !is_null($this->_options['labelAttribute']):
                    if (isset($info['attributes'][$this->_options['labelAttribute']])) {
                        $labels[$itemKey] = $info['attributes'][$this->_options['labelAttribute']];
                    }
                    else {
                        $labels[$itemKey] = $itemKey;
                    }
                    // no break here; we need the content!
                default:
                    $rowProcessed[$itemKey] = isset($info['content']) 
                        ? $info['content'] : ''; 
            }
        }
        // Set labels if extracted
        if (!$this->_options['labels'] && isset($labels)) {
            $this->setOption('labels', $labels);
        }
        return $rowProcessed;
    }
    
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
