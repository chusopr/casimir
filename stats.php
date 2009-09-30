<?php
require_once 'casimir-conf.php';
require_once 'Casimir.php';

$casimir = new Casimir();

require_once 'inc-header.php';
?>
<h3>Most followed short URLs</h3>
<?php
echo $casimir->getMostUsedSince();

require_once 'inc-footer.php';
?>
