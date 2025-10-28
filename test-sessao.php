<?php
session_start();
echo '<pre style="color:#fff;background:#333;padding:10px;">$_SESSION debug:' . PHP_EOL;
var_dump($_SESSION);
echo '</pre>';
exit;
