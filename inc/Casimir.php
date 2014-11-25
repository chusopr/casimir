<?php

// Failback _() function if gettext if not supported
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
	private $db;

	function __construct() {
	  $this->version = '1.1';
    $this->db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
    if ($this->db->connect_error) die(_('Could not connect to database'));
    $current_dir = dirname($_SERVER['PHP_SELF']);
    if ($current_dir == '/') $current_dir = '';
    $this->base_url = 'http://'.$_SERVER['SERVER_NAME'].$current_dir.'/';
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
		// Check if user has provided its list of preferred languages
		if (empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
			return false;

		// Let's try if we support user preferred language
		if (
				 (class_exists("Locale")) &&
				 ($this->tryLocale(Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE'])))
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

  function handleRequest() {
		if (preg_match("#^.*/\??([^=]+)$#i", $_SERVER['REQUEST_URI'], $regs)) {
		  $this->short = $this->db->real_escape_string($regs[1]);
		} else {
		  $this->short = '';
		}
		if ($this->short != '' && $this->short != basename($_SERVER['PHP_SELF'])) {
		  if ($location = $this->getLong($this->short)) {
		  	$this->updateUses($this->short);
		    header('Status: 301 Moved Permanently', false, 301);
		    header('Location: '.$location);
		    exit;
		  } else {
		    $this->ok = false;
		    $this->msg = _('Sorry, but this short URL isn\'t in our database.');
		  }
		}

		if (defined('ACCESS_KEY') && ACCESS_KEY != '' && ACCESS_KEY != $this->access_key) {
		  $this->ok = false;
		  $this->msg = _('This Casimir instance is protected, you need an access key!');
		} else {
		  if (isset($_POST['long'])) {
		    list($this->ok, $this->short, $this->msg) = $this->addUrl($_POST['long'], isset($_POST['short']) && !is_null($_POST['short']) && $_POST['short'] != 'null' ? $_POST['short'] : '');
		  } elseif (isset($_GET['long'])) {
		    list($this->ok, $this->short, $this->msg) = $this->addUrl($_GET['long'], isset($_GET['short']) && !is_null($_GET['short']) && $_GET['short'] != 'null' ? $_GET['short'] : '');
		  }
		}
  }

  function showForm() {
    if ($this->msg != '') {
      echo '<p class="'.($this->ok ? 'success' : 'error').'">'.$this->msg.'</p>';
    }
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
	  if (RECAPTCHA)
	  {
	  ?>
            <script src='https://www.google.com/recaptcha/api.js'></script>
            <dd class="center"><div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_KEY; ?>"></div></dd>
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
    $q = 'SELECT short_url FROM casimir WHERE long_url="'.trim($this->db->real_escape_string($long)).'" ORDER BY creation_date DESC LIMIT 0,1';
    $result = $this->db->query($q);
    if ((!empty($result)) && ($result->num_rows > 0)) {
      $row = $result->fetch_object();
      return $row->short_url;
    } else {
      return false;
    }
  }

  function getLong($short) {
    $q = 'SELECT long_url FROM casimir WHERE short_url="'.trim($this->db->real_escape_string($short)).'"';
    $result = $this->db->query($q);
    if ((!empty($result)) && ($result->num_rows == 1)) {
      $row = $result->fetch_object();
      return $row->long_url;
    } else {
      return false;
    }
  }

  function addUrl($long, $short = '') {
    // The CAPTCHA is the first one to be checked. This way, we save database queries
    if (RECAPTCHA)
    {
      if (!array_key_exists('g-recaptcha-response', $_POST))
        return array(false, '', _('Input provided by user is not valid'));
      $recaptcha_verify_url = (RECAPTCHA_HTTPS? 'https':'http') . '://www.google.com/recaptcha/api/siteverify';
      $recaptcha_response = file_get_contents("$recaptcha_verify_url?secret=" . RECAPTCHA_SECRET . "&response={$_POST['g-recaptcha-response']}&remoteip={$_SERVER['REMOTE_ADDR']}");
      if (empty($recaptcha_response)) return array(false, '', _('An error occurred trying to validate CAPTCHA'));
      $recaptcha_answer = json_decode($recaptcha_response);
      if (!$recaptcha_answer->success)
      {
        $error_message = sprintf(_('Unknown error: %s'), $recaptcha_answer->{'error-codes'}[0]);
        switch ($recaptcha_answer->{'error-codes'}[0])
	{
	  case 'missing-input-secret':
	  case 'invalid-input-secret':
	    $error_message = _('reCAPTCHA account is not correctly configured for this site');
	    break;
	  case 'missing-input-response':
	  case 'invalid-input-response':
	    $error_message = _('Input provided by user is not valid');
	    break;
	}
        return array(false, '', $error_message);
      }
    }
    $long = trim($this->db->real_escape_string($long));
    if ($long == '') {
      return array(false, '', _('You must at least enter a long URL!'));
    } elseif (!preg_match("#^https?://#", $long)) {
      return array(false, '', _('Your URL must start with either "http://" or "https://"!'));
    } elseif (substr($long, 0, strlen($this->base_url)) == $this->base_url) {
      return array(false, '', _('This is already a shorten URL!'));
    }

    $existing_short = $this->getShort($long);
    $short = trim($this->db->real_escape_string($short));
    if ($short != '') {
    	if (!preg_match("#^[a-zA-Z0-9_-]+$#", $short)) {
        return array(false, '', _('This short URL is not authorized!'));
    	} elseif (strlen($short) > 50) {
        return array(false, '', _('This short URL is not short enough! Hint: 50 chars allowed...'));
    	}
    }
    $existing_long = $this->getLong($short);
    switch(true) {
    	case ($short == '' && $existing_short):
    		$short = $existing_short;
        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
        return array(true, $short, _('A short URL already exists for this long URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>');
    		break;
    	case ($short == '' && !$existing_short):
	      $short = $this->getRandomShort();

	      $query = 'INSERT INTO casimir (short_url, long_url, creation_date) VALUES ("'.$short.'", "'.$long.'", NOW())';
	      if ($this->db->query($query)) {
	        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
	        return array(true, $short, _('Congratulations, you created this new short URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>');
	      } else {
	        return array(false, $short, sprintf(_('Something went wrong: %s %s'), $this->db->errno, $this->db->error));
	      }
    		break;
    	case ($short != '' && $existing_long && $long == $existing_long):
    	  $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
        return array(true, $short, _('This short URL already exists and is associated with the same long URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>');
    		break;
    	case ($short != '' && $existing_long && $existing_long != $long):
        return array(false, $short, _('This short URL already exists and is associated with this other long URL:') . '<br /><a href="'.$existing_long.'">'.$existing_long.'</a>');
    		break;
    	case ($short != '' && !$existing_short):
	      $query = 'INSERT INTO casimir (short_url, long_url, creation_date) VALUES ("'.$short.'", "'.$long.'", NOW())';
        if ($this->db->query($query)) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
	        return array(true, $short, _('Congratulations, you created this new short URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>');
        } else {
          return array(false, $short, sprintf(_('Something went wrong: %s %s'), $this->db->errno, $this->db->error));
        }
    		break;
    	case ($short != '' && !$existing_long):
    		// Same as previous???
	      $query = 'INSERT INTO casimir (short_url, long_url, creation_date) VALUES ("'.$short.'", "'.$long.'", NOW())';
        if ($this->db->query($query)) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
	        return array(true, $short, _('Congratulations, you created this new short URL:') . '<br /><a href="'.$short_url.'">'.$short_url.'</a>');
        } else {
          return array(false, $short, sprintf(_('Something went wrong: %s %s'), $this->db->errno, $this->db->error));
        }
     		break;
 		}
 		return array(false, '', _('This should never happen...'));
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
    $query = 'INSERT INTO casimir_stats (short_url, use_date) VALUES ("'.trim($this->db->real_escape_string($short)).'", NOW())';
    $this->db->query($query);
    $query = 'UPDATE casimir SET last_use_date=NOW(), uses=uses+1 WHERE short_url="'.trim($this->db->real_escape_string($short)).'"';
    return ($this->db->query($query) !== false);
  }

  function getMostUsedSinceDate($since = '1970-01-01 00:00:01', $nb = 10) {
    $query = 'SELECT s.short_url, COUNT(*) AS uses, c.long_url FROM casimir_stats s, casimir c WHERE s.short_url = c.short_url AND use_date >= "'.$this->db->real_escape_string($since).'" GROUP BY s.short_url ORDER BY uses DESC LIMIT 0,'.max(1,intval($nb));
    if ($res = $this->db->query($query)) {
	    $list = '<dl>';
	    if (!empty($res))
		while ($url = $res->fetch_object()) {
		    $list .= '<dt> <a href="'.$url->short_url.'" rel="nofollow" >'.$url->short_url.'</a> visited '.$url->uses.' time(s) </dt>';
        $list .= '<dd><a href="'.$url->long_url.'">'.htmlspecialchars($url->long_url).'</a></dd>';
	    }
	    $list .= '</dl>';
      return $list;
    } else {
    	return false;
    }
  }

  function getMostUsedLastDays($days = 7, $nb = 10) {
    return $this->getMostUsedSinceDate(date("Y-m-d H:i:s", time() - $days * 24*60*60), $nb);
  }
}
