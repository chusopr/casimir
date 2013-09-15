<?php
require_once 'inc/conf.php';
require_once 'inc/Casimir.php';

$casimir = new Casimir();

require_once 'inc/header.php';
?>
<h3><?php echo _("Tools"); ?></h3>
<h4><?php echo _("Bookmarklet"); ?></h4>
<div id="bookmarklet"><?php printf(_("Drag this bookmarklet into your toolbar: %s"), $casimir->showBookmarklet()); ?></div>
<?php
require_once 'inc/footer.php';
?>
