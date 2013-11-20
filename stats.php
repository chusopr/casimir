<?php
require_once 'inc/conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();

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
    echo $casimir->getMostUsedLastDays(1, 5);
    ?>
  </div>
  <div id="lastweek">
    <h4><?php echo _("Last week"); ?></h4>
    <?php
    echo $casimir->getMostUsedLastDays(7, 5);
    ?>
  </div>
  <div id="lastmonth">
    <h4><?php echo _("Last month"); ?></h4>
    <?php
    echo $casimir->getMostUsedLastDays(30, 5);
    ?>
  </div>
  <div id="ever">
    <h4><?php echo _("Ever"); ?></h4>
    <?php
    echo $casimir->getMostUsedSinceDate();
    ?>
  </div>
</div>

<?php
require_once 'inc/footer.php';
?>
