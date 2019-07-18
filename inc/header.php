<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title><?php echo ((defined('TITLE')) && (trim(TITLE) != "")) ? TITLE : INSTANCE_NAME; ?></title>
  <?php
    if ((defined('DESCRIPTION')) && (trim(DESCRIPTION) != ""))
    {
    ?>
      <meta name="description" content="<?php echo DESCRIPTION; ?>"/>
    <?php
    }
  ?>
  <link rel="stylesheet" type="text/css" media="screen" href="screen.css" />
  <meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
  <?php
  if (file_exists('user/screen.css')) {
    echo '<link rel="stylesheet" type="text/css" media="screen" href="user/screen.css" />';
  }
  ?>
  <link rel="icon" type="image/png" href="img/casimir.png" />
</head>

<body onload="document.getElementById('long').focus();">
  <div id="main">
    <div style="float: left">
      <script type="text/javascript"><!--
        google_ad_client = "ca-pub-9329384101419370";
        /* alar.ga */
        google_ad_slot = "3101107745";
        google_ad_width = 728;
        google_ad_height = 90;
      //-->
      </script>
      <script type="text/javascript"
        src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
      </script>

      <form action="http://www.google.es" id="cse-search-box">
        <div>
          <input type="hidden" name="cx" value="partner-pub-9329384101419370:4298639347" />
          <input type="hidden" name="ie" value="UTF-8" />
          <label>Buscar en Internet:</label> <input type="text" name="q" size="20" />
          <input type="submit" name="sa" value="Buscar" />
        </div>
      </form>
      <script type="text/javascript" src="http://www.google.es/coop/cse/brand?form=cse-search-box&amp;lang=es"></script>


      <h1><a href="<?php echo $casimir->base_url; ?>"><?php echo INSTANCE_NAME; ?></a></h1>
