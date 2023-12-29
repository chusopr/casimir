<?php
require_once 'inc/conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();

$mostused_day = $casimir->getMostUsedLastDays(1, 5);
$mostused_week = $casimir->getMostUsedLastDays(7, 5);
$mostused_month = $casimir->getMostUsedLastDays(30, 5);
$mostused_ever = $casimir->getMostUsedSinceDate();

require_once 'inc/header.php';
?>
<h3><?php echo _("Most followed short URLs"); ?></h3>
<ul class="tablinks">
  <li><a href="#lastday"><?php echo _("Last day"); ?></a></li>
  <li><a href="#lastweek"><?php echo _("Last week"); ?></a></li>
  <li><a href="#lastmonth"><?php echo _("Last month"); ?></a></li>
  <li><a href="#ever"><?php echo _("Ever"); ?></a></li>
</ul>
<div class="tabs">
  <div id="lastday">
    <h4><?php echo _("Last day"); ?></h4>
    <?php
    echo $mostused_day;
    ?>
  </div>
  <div id="lastweek">
    <h4><?php echo _("Last week"); ?></h4>
    <?php
    echo $mostused_week;
    ?>
  </div>
  <div id="lastmonth">
    <h4><?php echo _("Last month"); ?></h4>
    <?php
    echo $mostused_month;
    ?>
  </div>
  <div id="ever">
    <h4><?php echo _("Ever"); ?></h4>
    <?php
    echo $mostused_ever;
    ?>
  </div>
</div>

<?php
require_once 'inc/footer.php';
