<?php
require_once './user/casimir-conf.php';
require_once './inc/Casimir.php';

$casimir = new Casimir();
echo "<html><body><pre>";
echo $casimir->GetUrlHttpHead("http://xena.ww7.be/neoskills/html/BP/statuts/statuts.html");
echo "</pre><br /><pre>";
echo $casimir->GetUrlHtmlTitle("http://xena.ww7.be/neoskills/html/BP/statuts/statuts.html");
echo "</pre></body></html>";
//require_once 'inc/header.php';

//$casimir->showForm();

//require_once 'inc/footer.php';
?>

