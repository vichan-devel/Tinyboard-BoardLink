<?php
chdir('..');
$brd = basename(dirname(__FILE__));
require_once('boardlink/main.php');
require_once($brd.'/config.php');
$b->configure_callback();
?>
