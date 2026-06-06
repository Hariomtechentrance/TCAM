<?php
/**
 * TCAM Coach Logout
 */
session_start();
session_unset();
session_destroy();
header('Location: coach-login.php');
exit;
