<?php

// Failback _() function if gettext is not supported
if (!function_exists("_"))
{
  function _($s)
  {
    return $s;
  }
}

class Casimir {
  public $version;
  public $base_url;
  public $short;
  public $msg;
  public $ok;
  public $access_key;
  public $status = 200;
  private $db;
  private $locale;
  private $captcha_service;

  function __construct() {
    $this->version = '2.2';
    try {
      if (defined('DB_URL')) {
        $this->db = new PDO(DB_URL, username: defined('DB_USER')? DB_USER : null, password: defined('DB_PASSWORD')? DB_PASSWORD : null);
      }
      else {
        if (defined('MYSQL_HOST'))
          error_log("Casimir configuration warning: The old MYSQL_* configuration settings are being used. Those settings are deprecated and may be removed in future versions. It's advised that you migrate your configuration to use DB_* settings. Check user/casimir-conf.php.example for an example");
        $this->db = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
      }
      // Make sure exceptions are not thrown for SQL errors
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    } catch (PDOException $e) {
      $this->status = 500;
      $this->ok = false;
      $this->msg = _('Unable to connect to the database');
    }
    $current_dir = dirname($_SERVER['PHP_SELF']);
    if ($current_dir == '/') $current_dir = '';
    $this->base_url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$current_dir.'/';
    $this->short = '';
    $this->msg = '';
    $this->ok = true;
    $this->access_key = '';
    if (isset($_GET['access_key'])) {
      $this->access_key = $_GET['access_key'];
    } elseif (isset($_POST['access_key'])) {
      $this->access_key = $_POST['access_key'];
    }
    $this->setLocale();
    if (defined('RECAPTCHA') && RECAPTCHA)
      $this->captcha_service = RECAPTCHA == 'hcaptcha'? 'h-':'g-re';
  }

  private function tryLocale($l)
  {
    if (empty($l)) return false;

    // First, test if we support this locale
    if (
         // We may support it with country code: xx_XX
         (!file_exists("locale/$l/LC_MESSAGES/casimir.mo")) &&
         // Or as a country-less language code: xx
         (!file_exists("locale/" . preg_replace("/_.*/", "", $l) . "/LC_MESSAGES/casimir.mo"))
       )
      return false;

    if (!@setlocale(LC_ALL, "$l.utf8"))
      return false;

    putenv("LC_ALL=$l");

    bindtextdomain("casimir", "locale");
    textdomain("casimir");

    return true;
  }

  private function setLocale()
  {
    $this->locale = false;
    // Check if user has provided its list of preferred languages
    if (empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
      return false;

    $this->locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    // Let's try if we support user preferred language
    if (
         (class_exists("Locale")) &&
         ($this->tryLocale($this->locale))
       )
    return true;

    // Get list of preferred languages as provided by user to try them all
    $languages = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
    if ((!empty($languages)) && (is_array($languages)))
      // Iterate over languages to find the first we support
      foreach ($languages as $l)
      {
        // Convert Accept-Language language to a locale one
        $l = preg_replace(array("/;.*/", "/-/"), array("", "_"), $l);
        if ($this->tryLocale($l))
          return true;
      }

    return false;
  }

  function handleRequest($api) {
    if (preg_match("#^.*/\??([^=]+)$#i", $_SERVER['REQUEST_URI'], $regs)) {
      $this->short = $regs[1];
    } else {
      $this->short = '';
    }
    if ($this->short != '' && $this->short != basename($_SERVER['PHP_SELF'])) {
      if ($location = $this->getLong($this->short)) {
        $this->updateUses($this->short);
        header('Status: 301 Moved Permanently', false, 301);
        header('Location: '.$location);
        exit;
      } elseif ($location === NULL) {
        $this->status = 500;
        $this->ok = false;
        $this->msg = _('Internal error finding URL.');
      } else {
        $this->status = 404;
        $this->ok = false;
        $this->msg = _('Sorry, but this short URL isn\'t in our database.');
      }
    }

    if (defined('ACCESS_KEY') && ACCESS_KEY != '' && ACCESS_KEY != $this->access_key) {
      $this->status = 401;
      $this->ok = false;
      $this->msg = _('This Casimir instance is protected, you need an access key!');
    } else {
      if (isset($_POST['long'])) {
        list($this->ok, $this->short, $this->msg, $this->status) = $this->addUrl($_POST['long'], isset($_POST['short']) && !is_null($_POST['short']) && $_POST['short'] != 'null' ? $_POST['short'] : '', $api);
      } elseif (isset($_GET['long'])) {
        list($this->ok, $this->short, $this->msg, $this->status) = $this->addUrl($_GET['long'], isset($_GET['short']) && !is_null($_GET['short']) && $_GET['short'] != 'null' ? $_GET['short'] : '', $api);
      }
    }
  }

  function showForm() {
    ?>
    <form action="<?php echo $this->base_url; ?>" method="post">
      <?php
      if (defined('ACCESS_KEY') && ACCESS_KEY != '') {
        ?>
        <input type="hidden" name="access_key" id="access_key" value="<?php echo $this->access_key; ?>" />
        <?php
      }
      ?>
      <dl>
        <dt><label for="long"><?php echo _("Enter a long URL:"); ?></label></dt>
        <dd><input type="text" name="long" id="long" size="80" value="<?php echo ($this->ok ? '' : (isset($_POST['long']) ? $_POST['long'] : (isset($_GET['long']) ? $_GET['long'] : ''))); ?>" /></dd>
        <dt><label for="short"><?php echo _("Optionally, define your own short URL:"); ?></label></dt>
        <dd><?php echo $this->base_url.(USE_REWRITE ? '' : '?'); ?><input type="text" name="short" id="short" size="20" maxlength="255" value="<?php echo ($this->ok ? '' : (isset($_POST['short']) ? $_POST['short'] : (isset($_GET['short']) ? $_GET['short'] : ''))); ?>" /></dd>
        <dt></dt>
        <?php
          if (defined("RECAPTCHA") && RECAPTCHA)
          {
          ?>
            <script src='<?php
              echo $this->captcha_service == "h-"? "https://www.hcaptcha.com/1/api.js":"https://www.google.com/recaptcha/api.js";
              if ($this->locale)
                echo "?hl=".substr($this->locale, 0, 2);
            ?>'></script>
            <dd class="center"><div class="<?php echo $this->captcha_service; ?>captcha" data-sitekey="<?php echo RECAPTCHA_KEY; ?>"></div></dd>
          <?php
          }
        ?>
        <dd class="center"><input type="submit" name="submit" id="submit" value="<?php echo _("Create!"); ?>" /></dd>
      </dl>
    </form>
    <?php
  }

  function showBookmarklet() {
    ob_start();
    ?>
    <a href="javascript:var url='<?php echo $this->base_url; ?>?<?php if (defined('ACCESS_KEY') && ACCESS_KEY != '' && ACCESS_KEY == $this->access_key) { echo 'access_key='.ACCESS_KEY.'&'; } ?>long='+encodeURIComponent(location.host=='maps.google.com'?document.getElementById('link').href:location.href);var short=prompt('<?php echo _("Do you want to define your own short URL? (leave empty if you don\'t)"); ?>','');if(short!=''){url=url+'&amp;short='+short;}location.href=url;">+<?php echo INSTANCE_NAME; ?></a>
    <?php
    return ob_get_clean();
  }

  function getShort($long) {
    $q = $this->db->prepare('SELECT short_url FROM casimir WHERE long_url=:long_url ORDER BY creation_date DESC LIMIT 1 OFFSET 0');
    $q->bindParam(':long_url', $long);
    if (!$q->execute()) {
      $this->msg = _("Internal error checking for duplicates");
      return NULL;
    }
    if ($q->rowCount() > 0) {
      return $q->fetchObject()->short_url;
    } else {
      return false;
    }
  }

  function getLong($short) {
    $q = $this->db->prepare('SELECT long_url FROM casimir WHERE short_url=:short_url');
    $q->bindParam(':short_url', $short);
    if (!$q->execute()) {
      $this->msg = _("Internal error checking for short URL availability");
      return NULL;
    }
    if ($q->rowCount() == 1) {
      return $q->fetchObject()->long_url;
    } else {
      return false;
    }
  }

  function addUrl($long, $short = '', $api = false) {
    // The CAPTCHA is the first one to be checked. This way, we save database queries
    if (defined("RECAPTCHA") && RECAPTCHA && $api==false)
    {
      if (!array_key_exists("{$this->captcha_service}captcha-response", $_POST))
        return array(false, '', _('Input provided by user is not valid'), 400);
      $recaptcha_verify_url = (RECAPTCHA_HTTPS? 'https':'http') . '://' . ($this->captcha_service == 'h-'? 'hcaptcha.com/siteverify':'www.google.com/recaptcha/api/siteverify');
      $recaptcha_response = file_get_contents("$recaptcha_verify_url?secret=" . RECAPTCHA_SECRET . "&response={$_POST["{$this->captcha_service}captcha-response"]}&remoteip={$_SERVER['REMOTE_ADDR']}");
      if (empty($recaptcha_response)) return array(false, '', _('An error occurred trying to validate CAPTCHA'), 500);
      $recaptcha_answer = json_decode($recaptcha_response);
      if (!$recaptcha_answer->success)
      {
        $error_message = sprintf(_('Unknown error: %s'), $recaptcha_answer->{'error-codes'}[0]);
        switch ($recaptcha_answer->{'error-codes'}[0])
        {
          case 'missing-input-secret':
          case 'invalid-input-secret':
            $this->status = 500;
            $error_message = _('reCAPTCHA account is not correctly configured for this site');
            break;
          case 'missing-input-response':
          case 'invalid-input-response':
            $this->status = 400;
            $error_message = _('Input provided by user is not valid');
            break;
        }
        return array(false, '', $error_message, $this->status);
      }
    }
    if ($long == '')
      return array(false, '', _('You must at least enter a long URL!'), 400);
    elseif (!preg_match("#^https?://#", $long))
      return array(false, '', _('Your URL must start with either "http://" or "https://"!'), 400);
    elseif (substr($long, 0, strlen($this->base_url)) == $this->base_url) // FIXME this only works with the same URL scheme
      return array(false, '', _('This is already a shorten URL!'), 400);

    $existing_short = $this->getShort($long);
    if ($existing_short === NULL)
      return array(false, '', $this->msg, 500);

    if ($short != '') {
      if (!preg_match("#^[a-zA-Z0-9_-]+$#", $short))
        return array(false, '', _('This short URL is not allowed!'), 403);
      elseif (strlen($short) > 50)
        return array(false, '', _('This short URL is not short enough! Hint: 50 chars allowed...'), 400);
    }
    $existing_long = $this->getLong($short);
    if ($existing_long === NULL)
      return array(false, '', $this->msg, 500);

    switch(true) {
      case ($short == '' && $existing_short):
        $short = $existing_short;
        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
        return array(true, $short, _('A short URL already exists for this long URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>', 200);
        break;
      case ($short == '' && !$existing_short):
        $short = $this->getRandomShort();

        $query = $this->db->prepare('INSERT INTO casimir (short_url, long_url, creation_date) VALUES (:short_url, :long_url, NOW())');
        $query->bindParam(':short_url', $short);
        $query->bindParam(':long_url', $long);
        if ($query->execute()) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
          return array(true, $short, _('Congratulations, you created this new short URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>', 200);
        } else
          return array(false, $short, _('Internal error creating your short URL'), 500);
        break;
      case ($short != '' && $existing_long && $long == $existing_long):
        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
        return array(true, $short, _('This short URL already exists and is associated with the same long URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>', 200);
        break;
      case ($short != '' && $existing_long && $existing_long != $long):
        return array(false, $short, _('This short URL already exists and is associated with this other long URL:') . '<br /><a href="'.$existing_long.'">'.$existing_long.'</a>', 403);
        break;
      case ($short != '' && !$existing_short):
        $query = $this->db->prepare('INSERT INTO casimir (short_url, long_url, creation_date) VALUES (:short_url, :long_url, NOW())');
        $query->bindParam(':short_url', $short);
        $query->bindParam(':long_url', $long);
        if ($query->execute()) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
          return array(true, $short, _('Congratulations, you created this new short URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>', 200);
        } else
          return array(false, $short, _('Internal error creating your short URL'), 500);
        break;
      case ($short != '' && !$existing_long):
        // TODO Same as previous???
        $query = $this->db->prepare('INSERT INTO casimir (short_url, long_url, creation_date) VALUES (:short_url, :long_url, NOW())');
        $query->bindParam(':short_url', $short);
        $query->bindParam(':long_url', $long);
        if ($query->execute()) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
          return array(true, $short, _('Congratulations, you created this new short URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>', 200);
        } else
          return array(false, $short, _('Internal error creating your short URL'), 500);
         break;
     }
     return array(false, '', _('This should never happen...'), 500);
  }

  function getRandomShort() {
    $allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $short = '';
    while(strlen($short) < 4 || $this->getLong($short)) {
      $pos = rand(0, strlen($allowed_chars) - 1);
      $short .= substr($allowed_chars, $pos, 1);
    }
    return $short;
  }

  function updateUses($short) {
    $query = $this->db->prepare('INSERT INTO casimir_stats (short_url, use_date) VALUES (:short_url, NOW())');
    $query->bindParam(':short_url', $short);
    if (!$query->execute())
      error_log(sprintf(_('Unable to update stats for URL %s: %s'), $this->short_url, $this->db->errorInfo()[2]));
    $query = $this->db->prepare('UPDATE casimir SET last_use_date=NOW(), uses=uses+1 WHERE short_url=:short_url');
    $query->bindParam(':short_url', $short);
    if (!$query->execute())
      error_log(sprintf(_('Unable to update use count for URL %s: %s'), $this->short_url, $this->db->errorInfo()[2]));
  }

  function getMostUsedSinceDate($since = '1970-01-01 00:00:01', $nb = 10) {
    $query = $this->db->prepare('SELECT s.short_url, COUNT(*) AS uses, c.long_url FROM casimir_stats s, casimir c WHERE s.short_url = c.short_url AND use_date >= :use_date GROUP BY s.short_url, c.long_url ORDER BY uses DESC LIMIT '.intval($nb).' OFFSET 0');
    $query->bindParam(':use_date', $since);
    if ($query->execute())
    {
      $list = '<dl>';
      if ($query->rowCount() > 0)
        while ($url = $query->fetchObject()) {
          $list .= '<dt> <a href="'.$url->short_url.'" rel="nofollow" >'.$url->short_url.'</a> visited '.$url->uses.' time(s) </dt>';
          $list .= '<dd><a href="'.$url->long_url.'">'.htmlspecialchars($url->long_url).'</a></dd>';
        }
      $list .= '</dl>';
      return $list;
    } else {
      $this->ok = false;
      $this->status = 500;
      $this->msg = _('Internal error while getting latest most used URLs');
      return false;
    }
  }

  function getMostUsedLastDays($days = 7, $nb = 10) {
    return $this->getMostUsedSinceDate(date("Y-m-d H:i:s", time() - $days * 24*60*60), $nb);
  }
}
