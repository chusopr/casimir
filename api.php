<?php
require_once 'user-casimir-conf.php';
require_once 'Casimir.php';
$casimir = new Casimir();
$casimir->handleRequest();
header('Content-type: application/xml; charset=UTF-8');
echo '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
?>
<casimir stat="<?php echo ($casimir->ok ? 'ok' : 'error'); ?>">
  <?php
  if ($casimir->msg != '') {
    echo '<msg>'.$casimir->msg.'</msg>';
  }
  if ($casimir->ok) {
  	echo '<short>'.$casimir->base_url.(USE_REWRITE ? '' : '?').$casimir->short.'</short>';
  }
  ?>
</casimir>