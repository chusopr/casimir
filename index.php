<?php
require_once 'inc/conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();
$casimir->handleRequest();

require_once 'inc/header.php';

$casimir->showForm();

require_once 'inc/footer.php';
?>