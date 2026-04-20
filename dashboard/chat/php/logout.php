<?php
require_once '../../../auth/includes/auth_helpers.php';
ensureSessionStarted();
redirectTo('../../auth/logout.php');
?>
