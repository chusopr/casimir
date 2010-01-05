<?php
class Casimir {
  public $version;
	public $base_url;
	public $short;
	public $msg;
	public $ok;
	public $access_key;

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
	}

  function handleRequest() {
		if (ereg("^.*/\??([^=]+)$", $_SERVER['REQUEST_URI'], $regs)) {
		  $this->short = mysql_escape_string($regs[1]);
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
		    $this->msg = 'Sorry, but this short URL isn\'t in our database.';
		  }
		}
		
		if (defined('ACCESS_KEY') && ACCESS_KEY != '' && ACCESS_KEY != $this->access_key) {
		  $this->ok = false;
		  $this->msg = 'This Casimir instance is protected, you need an access key!';
		} else {  
		  if (isset($_POST['long'])) {
		    list($this->ok, $this->short, $this->msg) = $this->addUrl($_POST['long'], $_POST['short']); 
		  } elseif (isset($_GET['long'])) {
		    list($this->ok, $this->short, $this->msg) = $this->addUrl($_GET['long'], $_GET['short']); 
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
        <dd><input type="text" name="long" id="long" size="80" value="<?php echo ($this->ok ? '' : (isset($_POST['long']) ? $_POST['long'] : $_GET['long'])); ?>" /></dd>
        <dt><label for="short">Optionally, define your own short URL:</label></dt>
        <dd><?php echo $this->base_url.(USE_REWRITE ? '' : '?'); ?><input type="text" name="short" id="short" size="20" maxlength="255" value="<?php echo ($this->ok ? '' : (isset($_POST['short']) ? $_POST['short'] : $_GET['short'])); ?>" /></dd>
        <dt></dt>
        <dd class="center"><input type="submit" name="submit" id="submit" value="Create!" /></dd>
      </dl>
    </form>
  	<?php
  }

  function showBookmarklet() {
  	?>
  	<a href="javascript:var url='<?php echo $this->base_url; ?>?<?php if (defined('ACCESS_KEY') && ACCESS_KEY != '' && ACCESS_KEY == $this->access_key) { echo 'access_key='.ACCESS_KEY.'&'; } ?>long='+encodeURIComponent(location.host=='maps.google.com'?document.getElementById('link').href:location.href);var short=prompt('Do you want to define your own short URL? (leave empty if you don\'t)','');if(short!=''){url=url+'&short='+short;}location.href=url;">+Casimir</a>
  	<?php
  }
  
  function getShort($long) {
    $q = 'SELECT short_url FROM casimir WHERE long_url="'.trim(mysql_escape_string($long)).'" ORDER BY creation_date DESC LIMIT 0,1';
    $result = mysql_query($q);
    if (mysql_num_rows($result)) {
      $row = mysql_fetch_array($result);
      return $row['short_url'];
    } else {
      return false;
    }
  }

  function getLong($short) {
    $q = 'SELECT long_url FROM casimir WHERE short_url="'.trim(mysql_escape_string($short)).'"';
    $result = mysql_query($q);
    if (mysql_num_rows($result)) {
      $row = mysql_fetch_array($result);
      return $row['long_url'];
    } else {
      return false;
    }
  }
  
  function addUrl($long, $short = '') {
    $long = trim(mysql_escape_string($long));
    if ($long == '') {
      return array(false, '', 'You must at least enter a long URL!');
    } elseif (!ereg("^https?://", $long)) {
      return array(false, '', 'Your URL must start with either "http://" or "https://"!');
    }
    $existing_short = $this->getShort($long);
    $short = trim(mysql_escape_string($short));
    if ($short != '') {
    	if (!ereg("^[a-zA-Z0-9_-]+$", $short)) {
        return array(false, '', 'This short URL is not authorized!');
    	} elseif (strlen($short) > 50) {
        return array(false, '', 'This short URL is not short enough! Hint: 50 chars allowed...');
    	}
    }
    $existing_long = $this->getLong($short);
    //echo '<ul><li>short: '.$short.'</li><li>long: '.$long.'</li><li>existing short: '.$existing_short.'</li><li>existing long: '.$existing_long.'</li></ul>';
    switch(true) {
    	case ($short == '' && $existing_short):
    		$short = $existing_short;
        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
        return array(true, $short, 'A short URL already exists for this long URL: <a href="'.$short_url.'">'.$short_url.'</a>.');
    		break;
    	case ($short == '' && !$existing_short):
	      $short = $this->getRandomShort();
	      $query = 'INSERT INTO casimir (short_url, long_url, creation_date) VALUES ("'.$short.'", "'.$long.'", NOW())';
	      if (mysql_query($query)) {
	        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
	        return array(true, $short, 'Congratulations, you created this new short URL: <a href="'.$short_url.'">'.$short_url.'</a>.');
	      } else {
	        return array(false, $short, 'Something went wrong: '.mysql_error());
	      }
    		break;
    	case ($short != '' && $existing_long && $long == $existing_long):
        return array(true, $short, 'This short URL already exists and is associated with the same long URL.');
    		break;
    	case ($short != '' && $existing_long && $existing_long != $long):
        return array(false, $short, 'This short URL already exists and is associated with this other long URL: <a href="'.$existing_long.'">'.$existing_long.'</a>.');
    		break;
    	case ($short != '' && !$existing_short):
        $query = 'INSERT INTO casimir (short_url, long_url, creation_date) VALUES ("'.$short.'", "'.$long.'", NOW())';
        if (mysql_query($query)) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
          return array(true, $short, 'Congratulations, you created this new short URL: <a href="'.$short_url.'">'.$short_url.'</a>.');
        } else {
          return array(false, $short, 'Something went wrong: '.mysql_error());
        }
    		break;
    	case ($short != '' && !$existing_long):
    		// Same as previous???
        $query = 'INSERT INTO casimir (short_url, long_url, creation_date) VALUES ("'.$short.'", "'.$long.'", NOW())';
        if (mysql_query($query)) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
          return array(true, $short, 'Congratulations, you created this new short URL: <a href="'.$short_url.'">'.$short_url.'</a>.');
        } else {
          return array(false, $short, 'Something went wrong: '.mysql_error());
        }
     		break;
 		}
 		return array(false, '', 'This should never happen...');
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
    $query = "UPDATE casimir SET last_use_date=NOW(), uses=uses+1 WHERE short_url='".trim(mysql_escape_string($short))."'";
    return mysql_query($query);
  }
  	
  function getMostUsedSince($since = 0) {
    $query = "SELECT short_url, long_url, uses FROM casimir WHERE last_use_date >= ".$since." ORDER BY uses DESC LIMIT 0,10";
    if ($res = mysql_query($query)) {
	    $list = '<dl>';
	    while ($url = mysql_fetch_assoc($res)) {
	    	$list .= '<dt><a href="'.$url['short_url'].'">'.$url['short_url'].'</a> visited '.$url['uses'].' time(s)</dt>';
        $list .= '<dd><a href="'.$url['long_url'].'">'.$url['long_url'].'</a></dt>';
	    }
	    $list .= '</dl>';
      return $list;
    } else {
    	return false;
    }
  }
  
}
?>