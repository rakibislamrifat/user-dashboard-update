<?php
/**
 * Template Name: Logout
 */

session_start();
session_destroy();
header('Location: signin.php');
exit;
