<?php
require_once __DIR__ . '/includes/auth.php';

// Require user to be logged in
require_login();

// Redirect to their respective role directory
redirect_based_on_role();
