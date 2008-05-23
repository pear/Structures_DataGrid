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
 * and filenames. An XPath expression can be specified to extract data
 * rows from the given XML data.
 *
 * SUPPORTED OPTIONS:
 * 
 * - path:             (string) XPath used to extract the data rows. The default
 *                              is "*", which means all children of the context
 *                              (root) node.
 * - namespaces:       (array)  Pairs of prefix/namespace to register for XPath
 *                              processing
 * - fieldAttribute:  (string)  Which attribute of the XML source should be used
 *                              as column field name (only used if the XML source
 *                              has attributes).
 * - labelAttribute:  (string)  Which attribute of the XML source should be used
 *                              as column label (only used if 'generate_columns'
 *                              is true and the XML source has attributes).
 *
 * @example  bind-xml1.php  Bind a simple XML string
 * @example  bind-xml2.php  Bind a more complex XML string using XPath
 * @example  bind-atom.php  Bind an Atom feed with XPath and namespace
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
                'path'           => '*',
                'namespaces'     => array(),
                'xpath'          => null,
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

        $this->doc = $this->_loadDocument($xml);
        if (PEAR::isError($this->doc)) {
            return $this->doc;
        }

        if ($path = $this->_options['xpath']) {
            $this->_options['path'] = "$path/*";
        }

        $nodes = $this->doc->xpath($this->_options['path'], 
                                   $this->_options['namespaces']); 

        foreach ($nodes as $rowNode) {
            $this->_ar[] = $this->_processRow($rowNode);
        }

        if (!$this->_options['labels']) {
            $this->_options['labels'] = $this->_extractLabels($nodes);
        }

        if ($this->_ar && !$this->_options['fields']) {
            $this->setOption('fields', array_keys($this->_ar[0]));
        }

        $this->doc->free();

        return true;
    }

    /**
     * Load XML Document Model
     *
     * @access  private
     * @param   string  $xml    XML string or filename
     * @return  object  Structures_DataGrid_DataSource_XMLDomWrapper (PHP5) or
     *                  Structures_DataGrid_DataSource_XMLDomXmlWrapper (PHP4)
     */
    function _loadDocument($xml)
    {
        if (extension_loaded('dom')) {
            $doc = new Structures_DataGrid_DataSource_XMLDomWrapper();
        } elseif (extension_loaded('domxml')) {
            $doc = new Structures_DataGrid_DataSource_XMLDomXmlWrapper();
        } else {
            return PEAR::raiseError('DOM or DOM XML is required for XML processing');
        }

        $test = strstr($xml, '<') 
            ? $doc->loadString($xml) : $doc->loadFile($xml);

        if (!$test) {
            return PEAR::raiseError('XML couldn\'t be read.');
        }

        return $doc;
    }

    /**
     * Process a data row
     *
     * @access private
     * @param  object $node Row node
     * @return array        Fields and values
     */
    function _processRow($rowNode)
    {
        $row = array();
        foreach ($rowNode->childNodes() as $fieldNode) {
            $value = '';
            foreach ($fieldNode->childNodes() as $valueNode) {
                $value .= $this->doc->getXML($valueNode);
            }
            $nodeName = $fieldNode->nodeName();
            $fieldName = $nodeName;
            foreach ($fieldNode->attributes() as $name => $content) {
                if ($name == $this->_options['fieldAttribute']) {
                    $fieldName = $content;
                }
                $row["{$nodeName}attributes$name"][] = $content;
            }
            $row[$fieldName][] = $value;
        }
        foreach ($rowNode->attributes() as $name => $content)
        {
            $row["attributes$name"][] = $content;
        }
        $flat = array();
        foreach ($row as $field => $value) {
            if (count($value) > 1) {
                foreach ($value as $i => $item) {
                    $flat["$field$i"] = $item;
                }
            } else {
                $flat[$field] = $value[0];
            }
        }
        return $flat;
    }

    /**
     * Extract column labels
     *
     * @access private
     * @param  array  $nodes Array of row nodes
     * @return array         Fields and Labels  
     */
    function _extractLabels($nodes)
    {
        $labels = array();
        $labelAttr = $this->_options['labelAttribute'];
        $fieldAttr = $this->_options['fieldAttribute'];

        if (count($nodes) && $labelAttr) {
            foreach ($nodes[0]->childNodes() as $fieldNode) {
                if (($name = $fieldAttr) && $fieldNode->hasAttribute($name)) {
                    $fieldName = $fieldNode->getAttribute($name);
                } else {
                    $fieldName = $fieldNode->nodeName();
                }
                if ($fieldNode->hasAttribute($labelAttr)) {
                    $labels[$fieldName] 
                        = $fieldNode->getAttribute($labelAttr);
                }
            }
        }
        return $labels;
    }
}


/**
 * XML Document Model core Wrapper
 *
 * @access private
 * @package  Structures_DataGrid_DataSource_XML
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @category Structures
 */
class Structures_DataGrid_DataSource_XMLWrapper
{
    var $object;

    /**
     * Constructor
     *
     * @param object $domObject DOM or DOM XML object
     */
    function Structures_DataGrid_DataSource_XMLWrapper($domObject = null)
    {
        $this->object = $domObject;
    }

    /**
     * Decorate items of an iterable object
     *
     * @param  array  $mixed Array or Iterable DOM/DOM XML object
     * @return array         Array of wrapped items
     */
    function wrapArray($object)
    {
        $wrapped = array();
        $class = get_class($this);
        foreach ($object as $key => $value) {
            $wrapped[$key] = new $class($value);
        }
        return $wrapped;
    }
}

/**
 * XML Document Model DOM (PHP5) Wrapper
 *
 * @access private
 * @package  Structures_DataGrid_DataSource_XML
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @category Structures
 */
class Structures_DataGrid_DataSource_XMLDomWrapper
    extends Structures_DataGrid_DataSource_XMLWrapper
{
    /**
     * Load an XML string
     * 
     * @param  string $xml
     * @return bool   true on success, false on failure
     */
    function loadString($xml)
    {
        $this->object = new DOMDocument();
        $this->object->preserveWhiteSpace = false;
        return $this->object->loadXML($xml);
    }

    /**
     * Load an XML file
     * 
     * @param  string $filename
     * @return bool   true on success, false on failure
     */
    function loadFile($filename)
    {
        $this->object = new DOMDocument();
        $this->object->preserveWhiteSpace = false;
        return $this->object->load($filename);
    }

    /**
     * Run an xpath query, registering namespaces
     *
     * @param  string $query      XPath query
     * @param  array  $namespaces prefix/uri pairs
     * @return array              Nodes found
     */
    function xpath($query, $namespaces)
    {
        $xpath = new DOMXPath($this->object);
        foreach ($namespaces as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }

        return $this->wrapArray($xpath->query($query)); 
    }

    /**
     * Return child nodes
     *
     * @return array Child nodes
     */
    function childNodes()
    {
        return $this->wrapArray($this->object->childNodes);
    }

    /**
     * Dump a node into an XML string
     *
     * @param  object $node Node to dump
     * @return string       XML
     */
    function getXML($node)
    {
        return $this->object->saveXML($node->object);
    }

    /**
     * Get the node name
     *
     * @return string
     */
    function nodeName()
    {
        return $this->object->nodeName;
    }

    /**
     * Get all node's attributes
     *
     * @return array name/value pairs
     */
    function attributes()
    {
        $attributes = array();
        foreach ($this->object->attributes as $item) {
            $attributes[$item->name] = $item->value;
        }
        return $attributes;
    }

    /**
     * Check for attribute existence
     *
     * @return bool
     */
    function hasAttribute($name)
    {
        return $this->object->hasAttribute($name);
    }

    /**
     * Get an attribute value
     *
     * @param  string $name
     * @return string
     */
    function getAttribute($name)
    {
        return $this->object->getAttribute($name);
    }

    /**
     * Free document memory
     */
    function free()
    {
        $this->object = null;
    }
}

/**
 * XML Document Model DOM XML (PHP4) Wrapper
 *
 * @access private
 * @package  Structures_DataGrid_DataSource_XML
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @category Structures
 */
class Structures_DataGrid_DataSource_XMLDomXmlWrapper
    extends Structures_DataGrid_DataSource_XMLWrapper
{
    /**
     * Load an XML string
     * 
     * @param  string $xml
     * @return bool   true on success, false on failure
     */
    function loadString($xml)
    {
        $this->object = domxml_open_mem($xml, DOMXML_LOAD_DONT_KEEP_BLANKS);
        return (bool) $this->object;
    }

    /**
     * Load an XML file
     * 
     * @param  string $filename
     * @return bool   true on success, false on failure
     */
    function loadFile($filename)
    {
        $this->object = domxml_open_file($filename, DOMXML_LOAD_DONT_KEEP_BLANKS);
        return (bool) $this->object;
    }

    /**
     * Run an xpath query, registering namespaces
     *
     * @param  string $query      XPath query
     * @param  array  $namespaces prefix/uri pairs
     * @return array              Nodes found
     */
    function xpath($query, $namespaces)
    {
        $xpath = xpath_new_context($this->object);
        foreach ($namespaces as $prefix => $uri) {
            xpath_register_ns($xpath, $prefix, $uri);
        }
        $result = xpath_eval($xpath, $query);
        return $this->wrapArray($result->nodeset);
    }

    /**
     * Return child nodes
     *
     * @return array Child nodes
     */
    function childNodes()
    {
        return $this->wrapArray($this->object->child_nodes());
    }

    /**
     * Dump a node into an XML string
     *
     * @param  object $node Node to dump
     * @return string       XML
     */
    function getXML($node)
    {
        return $this->object->dump_node($node->object);
    }

    /**
     * Get the node name
     *
     * @return string
     */
    function nodeName()
    {
        return $this->object->node_name();
    }

    /**
     * Get all node's attributes
     *
     * @return array name/value pairs
     */
    function attributes()
    {
        $attributes = array();
        if ($items = $this->object->attributes()) {
            foreach ($items as $item) {
                $attributes[$item->name()] = $item->value();
            }
        }
        return $attributes;
    }

    /**
     * Check for attribute existence
     *
     * @return bool
     */
    function hasAttribute($name)
    {
        if (method_exists($this->object, 'has_attribute')) {
            return $this->object->has_attribute($name);
        }
        return false;
    }

    /**
     * Get an attribute value
     *
     * @param  string $name
     * @return string
     */
    function getAttribute($name)
    {
        return $this->object->get_attribute($name);
    }

    /**
     * Free document memory
     */
    function free()
    {
        $this->object->free();
        $this->object = null;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
