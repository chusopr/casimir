<?php
require_once 'user-casimir-conf.php';
require_once 'Casimir.php';

$casimir = new Casimir();
$casimir->handleRequest();

require_once 'inc-header.php';

$casimir->showForm();

require_once 'inc-footer.php';
?>