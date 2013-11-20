<?php
class Casimir {
  public $version;
	public $base_url;
	public $short;
	public $msg;
	public $ok;
	public $access_key;
  private $separator;
  private $sites;

	function __construct() {
	  $this->version = '1.1';
    mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD) or die('Could not connect to database');
    mysql_select_db(MYSQL_DATABASE) or die('Could not select database');
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
    $this->separator = (USE_REWRITE ? '/' : '?');

    // TODO: check correct syntax for sites names
    global $sites;
    $this->sites = array();
    if ((isset($sites)) && (is_array($sites)))
    {
      if (function_exists("idn_to_ascii"))
        $this->sites = $sites;
      else
        foreach ($sites as $site)
          // FIXME: This strictly checks for non-ASCII characters but
          // still accepts invalid domains
          if (preg_match('/[^\x00-\x7f]/', preg_replace("-/.*-", "", $site)))
            trigger_error("Removed IDN site $site since your PHP installation has no IDN support. Please install intl extension.", E_USER_WARNING);
          else
            $this->sites[] = $site;
    }
	}

  function handleRequest() {
		if (preg_match("#^.*/\??([^=]+)$#i", $_SERVER['REQUEST_URI'], $regs)) {
		  $this->short = mysql_real_escape_string($regs[1]);
		} else {
		  $this->short = '';
		}

    // if $url_list is empty we are probably using a legacy setup
    if (empty($this->sites) || (!is_array($this->sites)))
      $this->sites[] = preg_replace(array("-^http://-i", "-^www\.-i", "-/+$-"), "", $this->base_url);
    // Check which of the sites we are in
    $site = "";
    foreach ($this->sites as $s)
    {
      if (
           (preg_match("-^$s/-i", $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"])) ||
           (
             (function_exists("idn_to_ascii")) && // Check for IDN support
             (preg_match("_^".idn_to_ascii($s)."/_i", $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"])) // Check if it is an IDN
           )
         )
        $site = $s;
    }
    
    // TODO: if empty($site) ...

		if ($this->short != '' && $this->short != basename($_SERVER['PHP_SELF'])) {

      // Now check if it does exist a short URL like the one we are using to visit the site
		  if ($location = $this->getLong($site, $this->short)) {
		  	$this->updateUses($site, $this->short);
		    header('Status: 301 Moved Permanently', false, 301);
		    header('Location: '.$location);
		    exit;
		  } else {
		    $this->ok = false;
		    $this->msg = 'Sorry, but this short URL isn\'t in our database.';
		  }
		}
		
		if (defined('ACCESS_KEY') && ACCESS_KEY != '' && ACCESS_KEY != $this->access_key) {
		  $this->ok = false;
		  $this->msg = 'This Casimir instance is protected, you need an access key!';
		} else {
		  if (isset($_POST['long'])) {
        if ((array_key_exists("site", $_POST)) && (trim($_POST["site"]) != "") && (in_array($_POST["site"], $this->sites)))
          $site = $_POST["site"];
		    list($this->ok, $this->short, $this->msg) = $this->addUrl($_POST['long'], $site, isset($_POST['short']) && !is_null($_POST['short']) && $_POST['short'] != 'null' ? $_POST['short'] : ''); 
		  } elseif (isset($_GET['long'])) {
		    list($this->ok, $this->short, $this->msg) = $this->addUrl($_GET['long'], $site, isset($_GET['short']) && !is_null($_GET['short']) && $_GET['short'] != 'null' ? $_GET['short'] : ''); 
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
        <dt><label for="long">Enter a long URL:</label></dt>
        <dd><input type="text" name="long" id="long" size="80" value="<?php echo ($this->ok ? '' : (isset($_POST['long']) ? $_POST['long'] : (isset($_GET['long']) ? $_GET['long'] : ''))); ?>" /></dd>
        <dt><label for="short">Optionally, define your own short URL:</label></dt>
        <dd><?php 
          // Decide if we let user choose among different sites

          // First we check if we have configured a list of multiple sites
          if (!empty($this->sites))
          {
            echo "http://";
            // If only one site is configured, user doesn't need to choose
            if (count($this->sites) == 1)
              echo $this->sites[0];
            // Else, if there are more than one site, let user choose
            // which one to use with a drop down box
            else
            {
              echo '<select name="site">';
              foreach ($this->sites as $site)
              {
                $site = rtrim($site, "/");
                echo "<option ";
                if (
		     (preg_replace("-^www\.-i", "", $site) == preg_replace(array("-^http://-i", "-^www\.-", "-/+$-"), "", $this->base_url)) ||
		     (
                       (function_exists("idn_to_ascii")) && // Check for IDN support
		       (idn_to_ascii(preg_replace("-^www\.-i", "", $site)) == preg_replace(array("-^http://-i", "-^www\.-", "-/+$-"), "", $this->base_url))
		     )
		   )
                  echo 'selected="selected" ';
                echo "value='$site'>$site</option>";
              }
              echo '</select>';
            }
          }
          // Single site legacy setups
          else
            echo $this->base_url;
          echo $this->separator;
        ?><input type="text" name="short" id="short" size="20" maxlength="255" value="<?php echo ($this->ok ? '' : (isset($_POST['short']) ? $_POST['short'] : (isset($_GET['short']) ? $_GET['short'] : ''))); ?>" /></dd>
        <dt></dt>
        <dd class="center"><input type="submit" name="submit" id="submit" value="Create!" /></dd>
      </dl>
    </form>
  	<?php
  }

  function showBookmarklet() {
  	?>
  	<a href="javascript:var url='<?php echo $this->base_url; ?>?<?php if (defined('ACCESS_KEY') && ACCESS_KEY != '' && ACCESS_KEY == $this->access_key) { echo 'access_key='.ACCESS_KEY.'&'; } ?>long='+encodeURIComponent(location.host=='maps.google.com'?document.getElementById('link').href:location.href);var short=prompt('Do you want to define your own short URL? (leave empty if you don\'t)','');if(short!=''){url=url+'&amp;short='+short;}location.href=url;">+Casimir</a>
  	<?php
  }
  
  function getShort($site, $long) {
    $q = "SELECT short_url FROM casimir WHERE site = '".trim(mysql_real_escape_string($site))."' AND long_url='".trim(mysql_real_escape_string($long))."' ORDER BY creation_date DESC LIMIT 0,1";
    $result = mysql_query($q);
    if (mysql_num_rows($result)) {
      $row = mysql_fetch_array($result);
      return $row['short_url'];
    } else {
      return false;
    }
  }

  function getLong($site, $short) {
    $q = 'SELECT long_url FROM casimir WHERE site="'.trim(mysql_real_escape_string($site)).'" AND short_url="'.trim(mysql_real_escape_string($short)).'"';
    $result = mysql_query($q);
    if (mysql_num_rows($result)) {
      $row = mysql_fetch_array($result);
      return $row['long_url'];
    } else {
      return false;
    }
  }
  
  function addUrl($long, $site, $short = '') {
    $long = trim(mysql_real_escape_string($long));
    if ($long == '') {
      return array(false, '', 'You must at least enter a long URL!');
    } elseif (!preg_match("#^https?://#i", $long)) {
      return array(false, '', 'Your URL must start with either "http://" or "https://"!');
    } elseif (substr($long, 0, strlen($this->base_url)) == $this->base_url) { // TODO: multisite
      return array(false, '', 'This is already a shorten URL!');
    }

    $existing_short = $this->getShort($site, $long);
    $short = trim(mysql_real_escape_string($short));
    $site = trim(mysql_real_escape_string($site));
    if ($short != '') {
    	if (!preg_match("#^[a-zA-Z0-9_-]+$#", $short)) {
        return array(false, '', 'This short URL is not authorized!');
    	} elseif (strlen($short) > 50) {
        return array(false, '', 'This short URL is not short enough! Hint: 50 chars allowed...');
    	}
    }
    $existing_long = $this->getLong($site, $short);
    switch(true) {
    	case ($short == '' && $existing_short):
    		$short = $existing_short;
        $short_url = "http://$site{$this->separator}$short";
        return array(true, $short, 'A short URL already exists for this long URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>');
    		break;
    	case ($short == '' && !$existing_short):
	      $short = $this->getRandomShort($site);
	      
	      $query = "INSERT INTO casimir (site, short_url, long_url, creation_date) VALUES ('{$site}', '{$short}', '{$long}', NOW())";
	      if (mysql_query($query)) {
	        $short_url = "http://$site{$this->separator}$short";
	        return array(true, $short, 'Congratulations, you created this new short URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>');
	      } else {
	        return array(false, $short, 'Something went wrong: '.mysql_error());
	      }
    		break;
    	case ($short != '' && $existing_long && $long == $existing_long):
    	  $short_url = "http://$site{$this->separator}$short";
        return array(true, $short, 'This short URL already exists and is associated with the same long URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>');
    		break;
    	case ($short != '' && $existing_long && $existing_long != $long):
        return array(false, $short, 'This short URL already exists and is associated with this other long URL:<br /><a href="'.$existing_long.'">'.$existing_long.'</a>');
    		break;
    	case ($short != '' && !$existing_short):
	      $query = "INSERT INTO casimir (site, short_url, long_url, creation_date) VALUES ('{$site}', '{$short}', '{$long}', NOW())";
        if (mysql_query($query)) {
          $short_url = "http://$site{$this->separator}$short";
	        return array(true, $short, 'Congratulations, you created this new short URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>');
        } else {
          return array(false, $short, 'Something went wrong: '.mysql_error());
        }
    		break;
    	case ($short != '' && !$existing_long):
    		// Same as previous???
	      $query = "INSERT INTO casimir (site, short_url, long_url, creation_date, title_url ) VALUES ('{$site}', '{$short}', '{$long}', NOW())";
        if (mysql_query($query)) {
          $short_url = "http://$site{$this->separator}$short";
	        return array(true, $short, 'Congratulations, you created this new short URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>');
        } else {
          return array(false, $short, 'Something went wrong: '.mysql_error());
        }
     		break;
 		}
 		return array(false, '', 'This should never happen...');
  }
  
  function getRandomShort($site) {
    $allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  	$short = '';
  	while(strlen($short) < 4 || $this->getLong($site, $short)) {
  		$pos = rand(0, strlen($allowed_chars) - 1);
  		$short .= substr($allowed_chars, $pos, 1);
  	}
  	return $short;
  }
  
  function updateUses($site, $short) {
    $site = trim(mysql_real_escape_string($site));
    $short = trim(mysql_real_escape_string($short));
    $query = "INSERT INTO casimir_stats (site, short_url, use_date) VALUES ('$site', '$short', NOW())";
    mysql_query($query);
    $query = "UPDATE casimir SET last_use_date=NOW(), uses=uses+1 WHERE site = '$site' AND short_url = '$short'";
    return mysql_query($query);
  }
  	
  function getMostUsedSinceDate($since = '1970-01-01 00:00:01', $nb = 10) {
    $query = 'SELECT s.site, s.short_url, COUNT(*) AS uses, c.long_url FROM casimir_stats s, casimir c WHERE s.short_url = c.short_url AND use_date >= "'.mysql_real_escape_string($since).'" GROUP BY s.short_url ORDER BY uses DESC LIMIT 0,'.max(1,intval($nb));
    if ($res = mysql_query($query)) {
	    $list = '<dl>';
	    while ($url = mysql_fetch_assoc($res)) {
	    	$list .= "<dt> <a href='http://{$url['site']}{$this->separator}{$url['short_url']}' rel='nofollow'>{$url['site']}{$this->separator}{$url['short_url']}</a> visited {$url['uses']} time(s) </dt>";
        $list .= "<dd><a href='{$url['long_url']}'>".htmlspecialchars($url['long_url']).'</a></dd>';
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
?>
