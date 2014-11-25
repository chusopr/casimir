<?php
require_once 'inc/conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();
$casimir->handleRequest();

if ((RECAPTCHA) && ((!ini_get("allow_url_fopen")) || (!function_exists("json_decode"))))
  die(_("This site is not correctly configured: reCAPTCHA was enabled but the server does not met all requirements."));

require_once 'inc/header.php';

$casimir->showForm();

require_once 'inc/footer.php';
?>
