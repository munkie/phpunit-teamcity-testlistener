--TEST--
strtr() function - basic test for strtr()
--FILE--
<?php
/* Do not change this test it is a README.TESTING example. */
$trans = array("hello"=>"hi", "hi"=>"hello", "a"=>"A", "world"=>"planet");
var_dump(strtr("# hi all, I said hello world! #", $trans));
?>
--EXPECT--
string(32) "# hello All, I sAid hi planet! #"