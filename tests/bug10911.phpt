--TEST--
various Structures_DataGrid_DataSource_CSV functionality tests
--FILE--
<?php
    $list = array(
        'aaa,bbb',
        'aaa,"bbb"',
        '"aaa","bbb"',
        'aaa,bbb',
        '"aaa",bbb',
        '"aaa",   "bbb"',
        ',',
        'aaa,',
        ',"aaa"',
        '"",""',
        '"\\"","aaa"',
        '"""""",',
#       '""""",aaa',
#       '"\\""",aaa',
#       'aaa,"\\"bbb,ccc',
        'aaa,bbb   ',
        'aaa,"bbb   "',
        'aaa"aaa","bbb"bbb',
        'aaa"aaa""",bbb',
        'aaa"\\"a","bbb"'
    );

    require_once "Structures/DataGrid/DataSource/CSV.php";

    foreach ($list as $v) {
        $datasource = new Structures_DataGrid_DataSource_CSV();
        $datasource->bind($v);
        $data = $datasource->fetch();
        $row = @$data[0];
        var_dump($row);
    }
?>
--EXPECT--
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(3) "bbb"
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(3) "bbb"
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(3) "bbb"
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(3) "bbb"
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(3) "bbb"
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(3) "bbb"
}
array(2) {
  [0]=>
  string(0) ""
  [1]=>
  string(0) ""
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(0) ""
}
array(2) {
  [0]=>
  string(0) ""
  [1]=>
  string(3) "aaa"
}
array(2) {
  [0]=>
  string(0) ""
  [1]=>
  string(0) ""
}
array(2) {
  [0]=>
  string(2) "\""
  [1]=>
  string(3) "aaa"
}
array(2) {
  [0]=>
  string(2) """"
  [1]=>
  string(0) ""
}
array(1) {
  [0]=>
  string(7) """,aaa
"
}
array(1) {
  [0]=>
  string(8) "\"",aaa
"
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(10) "\"bbb,ccc
"
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(6) "bbb   "
}
array(2) {
  [0]=>
  string(3) "aaa"
  [1]=>
  string(6) "bbb   "
}
array(2) {
  [0]=>
  string(8) "aaa"aaa""
  [1]=>
  string(6) "bbbbbb"
}
array(2) {
  [0]=>
  string(10) "aaa"aaa""""
  [1]=>
  string(3) "bbb"
}
array(2) {
  [0]=>
  string(8) "aaa"\"a""
  [1]=>
  string(3) "bbb"
}