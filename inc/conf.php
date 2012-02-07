<?php
if (file_exists('user/casimir-conf.php')) {
  include_once 'user/casimir-conf.php';
} else {
  die('<h1>Error</h1><p>This instance of <a href="http://cas.im/ir">Cas.im/ir</a> is not yet configured.</p>');
}
?>