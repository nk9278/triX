<?php
require_once __DIR__ . '/includes/auth.php';

// Call logout function
logout();

// Redirect to login page
header("Location: login.php");
exit;
