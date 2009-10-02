<?php
require_once 'user/casimir-conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();

require_once 'inc/header.php';
?>
<h3>Most followed short URLs</h3>
<?php
echo $casimir->getMostUsedSince();

require_once 'inc/footer.php';
?>