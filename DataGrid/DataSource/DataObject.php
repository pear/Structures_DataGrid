<?php
/**
 * PEAR::DB_DataObject Data Source Driver
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
 * @package  Structures_DataGrid_DataSource_DataObject
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */


require_once 'Structures/DataGrid/DataSource.php';

/**
 * PEAR::DB_DataObject Data Source Driver
 *
 * This class is a data source driver for a PEAR::DB::DB_DataObject object
 *
 * SUPPORTED OPTIONS:
 *
 * - labels_property:   (string) The name of a property that you can set within
 *                               your DataObject. This property should contain 
 *                               the same kind of information as the 'labels' 
 *                               option. If the 'labels' option is set, this 
 *                               one will not be used.
 * - fields_property:   (string) The name of a property that you can set within
 *                               your DataObject. This property is expected to
 *                               contain the same kind of information as the
 *                               'fields' option. If the 'fields' option is set,
 *                               this one will not be used.
 * - fields_order_property: (string) The name of a property that you can set 
 *                               within your DataObject. It will be used to 
 *                               set the order in which fields are displayed, 
 *                               as long as you're not configuring this by 
 *                               adding/generating columns. Also requires the
 *                               fields_property to be set. 
 * - sort_property:     (string) The name of a property that you can set within
 *                               your DataObject. This property should contain 
 *                               an array of the form:
 *                               array("field1", "field1 DESC", ...)
 *                               If the data is already being sorted then this
 *                               this property's content will be appended 
 *                               to the current ordering.
 * - link_level:        (int)    The maximum link display level. If equal to 0
 *                               the links will not be followed.
 * - link_property:     (string) The name of a property you can set within a 
 *                               linked DataObject. This property should 
 *                               contain a array of field names that will
 *                               be used to display a string out of this 
 *                               linked DataObject.
 *                               Has no effect when link_level is 0.
 * - link_keep_key:     (bool)   Set this to true when you want to keep the
 *                               original values (usually foreign keys) of  
 *                               fields which are being replaced by their linked
 *                               values. The record will then contain additional
 *                               keys with "__key" prepended. This option only
 *                               makes sense with link_level higher than 0.
 *                               Example: if the country_code original value
 *                               is 'FR' and this is replaced by "France" from
 *                               the linked country table, then setting 
 *                               link_keep_key to true will keep the "FR" 
 *                               value in country_code__key.
 * - formbuilder_integration: (bool) DEPRECATED: use link_level and 
 *                               fields_order_property instead.
 *                               For BC, Setting this to true is equivalent to 
 *                               setting link_level to 3 and 
 *                               fields_order_property to 'fb_preDefOrder'.
 * - raw_count:         (bool)   If true: query all the records in order to
 *                               count them. This is needed when records are 
 *                               grouped (GROUP BY, DISTINCT, etc..), but
 *                               might be heavy.
 *                               If false: perform a smart count query with 
 *                               DB_DataObject::count().
 *
 * @example bind-dataobject.php  Bind a DB_DataObject to Structures_DataGrid
 *
 * @version  $Revision$
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid_DataSource_DataObject
 * @category Structures
 */
class Structures_DataGrid_DataSource_DataObject
    extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the DataObject
     *
     * @var object DB_DataObject
     * @access private
     */
    var $_dataobject;
    
    /**
     * Total number of rows 
     * 
     * This property caches the result of DataObject::count(), that 
     * can't be called after DataObject::fetch() (DataObject bug?).
     *
     * @var int
     * @access private
     */
     var $_rowNum = null;    
    
    /**
     * Constructor
     *
     * @param object DB_DataObject
     * @access public
     */
    function Structures_DataGrid_DataSource_DataObject()
    {
        parent::Structures_DataGrid_DataSource();

        $this->_addDefaultOptions(array(
                    'use_private_vars' => false,
                    'labels_property' => 'fb_fieldLabels',
                    'fields_property' => 'fb_fieldsToRender',
                    'fields_order_property' => null,
                    'sort_property' => 'fb_linkOrderFields',
                    'link_property' => 'fb_linkDisplayFields',
                    'link_level' => 0,
                    'link_keep_key' => false,
                    'formbuilder_integration' => false,
                    'raw_count' => false));
       
        $this->_setFeatures(array('multiSort' => true));
    }
  
    /**
     * Bind
     *
     * @param   object DB_DataObject    $dataobject     The DB_DataObject object
     *                                                  to bind
     * @param   array                   $options        Associative array of 
     *                                                  options.
     * @access  public
     * @return  mixed   True on success, PEAR_Error on failure
     */
    function bind(&$dataobject, $options = array())
    {
        if ($options) {
            $this->setOptions($options); 
        }

        if (is_subclass_of($dataobject, 'DB_DataObject')) {
            $this->_dataobject =& $dataobject;

            $mergeOptions = array();
            
            // Merging the fields and fields_property options
            if (!$this->_options['fields']) {
                if (($fieldsVar = $this->_options['fields_property'])
                    && isset($this->_dataobject->$fieldsVar)) {
                    $mergeOptions['fields'] = $this->_dataobject->$fieldsVar;

                    $fieldsOrderProperty = $this->_options['fields_order_property'];
                    if (is_null($fieldsOrderProperty) 
                            && $this->_options['formbuilder_integration']) {
                        $fieldsOrderProperty = 'fb_preDefOrder';
                    } 

                    if (!is_null($fieldsOrderProperty)) {
                        if (isset($this->_dataobject->$fieldsOrderProperty)) {
                            $ordered = array();
                            foreach ($this->_dataobject->$fieldsOrderProperty as
                                     $orderField) {
                                if (in_array($orderField,
                                             $mergeOptions['fields'])) {
                                    $ordered[] = $orderField;
                                }
                            }
                            $mergeOptions['fields'] =
                                array_merge($ordered,
                                            array_diff($mergeOptions['fields'],
                                                       $ordered));
                        }
                        foreach ($mergeOptions['fields'] as $num => $field) {
                            if (strstr($field, '__tripleLink_') ||
                                strstr($field, '__crossLink_') || 
                                strstr($field, '__reverseLink_')) {
                                unset($mergeOptions['fields'][$num]);
                            }
                        }
                    }
                }
            }

            // Merging the labels and labels_property options
            if ((!$this->_options['labels']) 
                && ($labelsVar = $this->_options['labels_property'])
                && isset($this->_dataobject->$labelsVar)) {
                
                $mergeOptions['labels'] = $this->_dataobject->$labelsVar;

            }

            if ($mergeOptions) {
                $this->setOptions($mergeOptions);
            }
                
            return true;
        } else {
            return PEAR::raiseError('The provided source must be a DB_DataObject');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @access  public
     * @return  array   The 2D Array of the records
     */    
    function &fetch($offset = 0, $len = null)
    {
        // Check to see if Query has already been submitted
        if ($this->_dataobject->getDatabaseResult()) {
            $this->_rowNum = $this->_dataobject->N;
        } else {
            // Caching the number of rows
            if (PEAR::isError($count = $this->count())) {
                return $count;
            } else {
                $this->_rowNum = $count;
            }
                    
            // Sorting
            if (($sortProperty = $this->_options['sort_property'])
                      && isset($this->_dataobject->$sortProperty)) {
                foreach ($this->_dataobject->$sortProperty as $sort) {
                    $this->sort($sort);
                }
            }
            
            // Limiting
            if ($offset) {
                $this->_dataobject->limit($offset, $len);
            } elseif ($len) {
                $this->_dataobject->limit($len);
            }
            
            $result = $this->_dataobject->find();
        }
        
        // Retrieving data
        $records = array();
        $linkLevel = $this->_options['link_level'];
        if (($linkLevel == 0) && $this->_options['formbuilder_integration']) {
            $linkLevel = 3;
        }
        if ($this->_rowNum) {
            $links = $this->_dataobject->links();
            $initial = true;
            while ($this->_dataobject->fetch()) {
                // Determine Fields
                if ($initial) {
                    if (!$this->_options['fields']) {
                        if ($this->_options['use_private_vars']) {
                            $this->_options['fields'] =
                                array_keys(get_object_vars($this->_dataobject));
                        } else {
                            $this->_options['fields'] =
                                array_keys($this->_dataobject->toArray());
                        }
                    }
                    $initial = false;
                }
                // Build DataSet
                $rec = array();
                foreach ($this->_options['fields'] as $fName) {
                    $getMethod = (strpos($fName, '_') !== false) 
                        ? 'get' . implode('', array_map('ucfirst', 
                                            explode('_', $fName)))
                        : 'get' . ucfirst($fName);
                    if (method_exists($this->_dataobject, $getMethod)) {
                        $rec[$fName] = $this->_dataobject->$getMethod();
                    } elseif (isset($this->_dataobject->$fName)) {                        
                        $rec[$fName] = $this->_dataobject->$fName;
                    } else {
                        $rec[$fName] = null;
                    }
                }
                
                // Get Linked Fields
                if ($linkLevel > 0) {
                    foreach (array_keys($rec) as $field) {
                        if (isset($links[$field]) &&
                            isset($this->_dataobject->$field) &&
                            ($linkedDo = $this->_dataobject->getLink($field)) &&
                            !PEAR::isError($linkedDo)) {
                            if ($this->_options['link_keep_key']) {
                                $rec["{$field}__key"] = $rec[$field];
                            }
                            $rec[$field] =$this->_getDataObjectString($linkedDo, $linkLevel);
                        }
                    }
                }
                                
                $records[] = $rec;
            }
        }

        // TODO: (maybe) free the result object here

        return $records;
    }

    /**
     * Represent a DataObject as a string, following links
     *
     * This method is a modified version of 
     * DB_DataObject_FormBuilder::getDataObjectString()
     *
     * @author Justin Patrin <papercrane@reversefold.com>
     * @author Olivier Guilyardi <olivier@samalyse.com>
     * @param DB_DataObject $do         The dataobject to get the display value
     *                                  for
     * @param int           $linkLevel  Maximum link level to follow
     * @param int           $level      The current recursion level. For 
     *                                  internal use only.
     * @return string                   String representing this dataobject
     * @access public
     */
    function _getDataObjectString(&$do, $linkLevel = 1, $level = 1) {
        if (!is_array($links = $do->links())) {
            $links = array();
        }

        $linkProperty = $this->_options['link_property'];
        if (isset($do->$linkProperty)) {
            $displayFields = $do->$linkProperty;
        } else {
            $displayFields = array_keys($do->table());
        }

        $ret = '';
        $first = true;
        foreach ($displayFields as $field) {
            if ($first) {
                $first = false;
            } else {
                $ret .= ', ';
            }
            if (isset($do->$field)) {
                if ($linkLevel > $level && isset($links[$field])) {
                    if ($subDo = $do->getLink($field)) {
                        $ret .= '('.$this->_getDataObjectString($subDo, $linkLevel, $level + 1).')';
                        continue;
                    }
                }
                $ret .= $do->$field;
            }
        }
        return $ret;
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number of records or a PEAR_Error
     */    
    function count()
    {
        if (is_null($this->_rowNum)) {
            if ($this->_dataobject->N) {
                $this->_rowNum = $this->_dataobject->N;
            } else {
                if ($this->_options['raw_count']) {
                    $clone = clone($this->_dataobject);
                    $clone->orderBy(); // Clear unneeded ordering
                    $test = $clone->find();
                    if (!is_numeric($test)) {
                        return PEAR::raiseError('Can\'t count the number of rows');
                    }
                    $clone->free();
                } else {
                    $test = $this->_dataobject->count();
                    if ($test === false) {
                        return PEAR::raiseError('Can\'t count the number of rows');
                    }
                }

                $this->_rowNum = $test;
            }
        }

        return $this->_rowNum;
    }

    /**
     * Converts a link key field name to its original name if needed
     *
     * @access protected
     * @param   string  $field  Field name
     * @return  string          Field name with "__key" suffix removed, if needed
     */
    function _convertLinkKey($field) 
    {
        if (($this->_options['link_level'] 
                    || $this->_options['formbuilder_integration'])
                && $this->_options['link_keep_key']
                && (substr($field, -5, 5) == '__key')) {
            $field = substr($field, 0, -5);
        }
        return $field;
    }

    /**
     * Sorts the dataobject.  This MUST be called before fetch.
     * 
     * @access  public
     * @param   mixed   $sortSpec   A single field (string) to sort by, or a 
     *                              sort specification array of the form:
     *                              array(field => direction, ...)
     * @param   string  $sortDir    Sort direction: 'ASC' or 'DESC'
     *                              This is ignored if $sortDesc is an array
     */
    function sort($sortSpec, $sortDir = null)
    {
        $db = $this->_dataobject->getDatabaseConnection();

        if (is_array($sortSpec)) {
            foreach ($sortSpec as $field => $direction) {
                $field = $this->_convertLinkKey($field);
                $field = $db->quoteIdentifier($field);
                $this->_dataobject->orderBy("$field $direction");
            }
        } else {
            $sortSpec = $this->_convertLinkKey($sortSpec);
            $sortSpec = $db->quoteIdentifier($sortSpec);
            if (is_null($sortDir)) {
                $this->_dataobject->orderBy($sortSpec);
            } else {
                $this->_dataobject->orderBy("$sortSpec $sortDir");
            }
        }
    }
    
    // This function is temporary until DB_DO bug #1315 is fixed
    // This removeds and variables from the DataObject that begins with _ or fb_
    function _fieldsFilter($value)
    {
        if (substr($value, 0, 1) == '_') {
            return false;
        } else if (substr($value, 0, 3) == 'fb_') {
            return false;
        } else if ($value == 'N') {
            return false;
        } else {
            return true;
        }
        
    }

}

/*
 * clone() replacement, (partly) by Aidan Lister <aidan@php.net>
 * borrowed from PHP_Compat 1.5.0
 */
if ((version_compare(phpversion(), '5.0') === -1) && !function_exists('clone')) {
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

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
