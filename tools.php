<?php
require_once 'inc/conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();

require_once 'inc/header.php';
?>
<h3>Tools</h3>
<h4>Bookmarklet</h4>
<div id="bookmarklet">Drag this bookmarklet into your toolbar: <?php $casimir->showBookmarklet(); ?></div>
<?php
require_once 'inc/footer.php';
?>