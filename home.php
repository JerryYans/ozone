<?php
session_start();
$uid = $_SESSION['uid'];
echo "hello ozone home \n";
echo "<h3>this is my test version</h3> \n";
echo "login user id is {$uid}";


