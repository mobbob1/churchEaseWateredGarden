<?php
require_once 'config.php';
require_once 'includes/SessionManager.php';

$sessionManager = new SessionManager($pdo);
$sessionManager->destroySession();

header('Location: index.php');
exit();

