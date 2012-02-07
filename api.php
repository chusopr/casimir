<?php
require_once 'inc/conf.php';
require_once 'inc/Casimir.php';
$casimir = new Casimir();
$casimir->handleRequest();
if (!isset($_GET['format']) || !in_array($_GET['format'], array('text', 'xml'))) {
  $format = DEFAULT_API_FORMAT;
} else {
  $format = $_GET['format'];
}
switch($format) {
  case 'text':
    header('Content-type: text/plain; charset=UTF-8');
    if ($casimir->ok) {
    	echo $casimir->base_url.(USE_REWRITE ? '' : '?').$casimir->short;
    } else {
      echo 'Error: '.$casimir->msg;
    }
    break;
  case 'xml':
    header('Content-type: application/xml; charset=UTF-8');
    echo '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'."\n";
    echo '<casimir stat="'.($casimir->ok ? 'ok' : 'error').'">';
    if ($casimir->msg != '') {
      echo '<msg>'.$casimir->msg.'</msg>';
    }
    if ($casimir->ok) {
    	echo '<short>'.$casimir->base_url.(USE_REWRITE ? '' : '?').$casimir->short.'</short>';
    }
    echo '</casimir>';
    break;
}
?>
