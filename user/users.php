<?php
require_once '../includes/auth.php';
// User should not be able to create or manage other users
redirect_based_on_role();
