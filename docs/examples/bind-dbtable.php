<?php
// basic guestbook class that extends DB_Table
class GuestBook_Table extends DB_Table
{
    var $col = array(
        // unique row ID
        'id' => array(
            'type'    => 'integer',
            'require' => true
        ),
        // first name
        'fname' => array(
            'type'    => 'varchar',
            'size'    => 32
        ),
        // last name
        'lname' => array(
            'type'    => 'varchar',
            'size'    => 64
        ),
        // email address
        'email' => array(
            'type'    => 'varchar',
            'size'    => 128,
            'require' => true
        ),
        // date signed
        'signdate' => array(
            'type'    => 'date',
            'require' => true
        )
    );
    var $idx = array();  // indices don't matter here
    var $sql = array(
        // multiple rows for a list 
        'list' => array( 
            'select' => 'id, signdate, CONCAT(fname, " ", lname) AS fullname',
            'order'  => 'signdate DESC'
        )
    );
}

// instantiate the extended DB_Table class
// (using an existing database connection and the table name 'guestbook')
$guestbook =& new GuestBook_Table($db, 'guestbook');

// Options for the bind() call
// (using the predefined query 'list' from the $sql array and a where
// condition)
$options = array('view' => 'list', 'where' => 'YEAR(signdate) = 2100');

// bind the guestbook object
// (if you don't generate any column yourself before rendering, three
// columns will be generated: id, signdate, fullname)
$test = $datagrid->bind($guestbook, $options);

// print binding error if any
if (PEAR::isError($test)) {
    echo $test->getMessage(); 
}
?>