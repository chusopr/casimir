<?php
require_once 'user/casimir-conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();

require_once 'inc/header.php';
?>
<h3>Most followed short URLs</h3>
<h4>Last 24 hours</h4>
<?php
echo $casimir->getMostUsedLastDays(1);
?>
<h4>Last 7 days</h4>
<?php
echo $casimir->getMostUsedLastDays(7);
?>
<h4>Last 30 days</h4>
<?php
echo $casimir->getMostUsedLastDays(30);
?>
<h4>Since the begining</h4>
<?php
echo $casimir->getMostUsedSinceDate();
?>

<?php
require_once 'inc/footer.php';
?>