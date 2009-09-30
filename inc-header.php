<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>

<head>
	<title>Casimir - short URL creation</title>
	<link rel="stylesheet" type="text/css" media="screen" href="screen.css" />
</head>

<body onload="document.getElementById('long').focus();">
	<div id="main">
    <div id="bookmarklet">Drag this bookmarklet into your toolbar: <?php $casimir->showBookmarklet(); ?></div>
		<h1><a href="<?php echo $casimir->base_url; ?>">Casimir</a></h1>
		<h2>Yet Another URL Shortener</h2>
		