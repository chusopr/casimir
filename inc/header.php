<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?php echo INSTANCE_NAME; ?></title>
	<link rel="stylesheet" type="text/css" media="screen" href="screen.css" />
  <?php
  if (file_exists('user/screen.css')) {
  	echo '<link rel="stylesheet" type="text/css" media="screen" href="user/screen.css" />';
  }
  ?>
  <link rel="icon" type="image/png" href="img/casimir.png" />
</head>

<body onload="document.getElementById('long').focus();">
	<div id="main">
		<h1><a href="<?php echo $casimir->base_url; ?>"><?php echo INSTANCE_NAME; ?></a></h1>
