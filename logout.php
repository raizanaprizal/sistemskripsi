<?php
require_once 'database.php';
require_once 'session.php';
session_destroy();
header("Location: " . BASE_URL . "/index.php");
exit;
 