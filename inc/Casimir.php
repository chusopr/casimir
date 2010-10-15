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
        <dt><label for="long">Enter a long URL:</label></dt>
        <dd><input type="text" name="long" id="long" size="80" value="<?php echo ($this->ok ? '' : (isset($_POST['long']) ? $_POST['long'] : (isset($_GET['long']) ? $_GET['long'] : ''))); ?>" /></dd>
        <dt><label for="short">Optionally, define your own short URL:</label></dt>
        <dd><?php echo $this->base_url.(USE_REWRITE ? '' : '?'); ?><input type="text" name="short" id="short" size="20" maxlength="255" value="<?php echo ($this->ok ? '' : (isset($_POST['short']) ? $_POST['short'] : (isset($_GET['short']) ? $_GET['short'] : ''))); ?>" /></dd>
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
    } elseif (substr($long, 0, strlen($this->base_url) - 1) == $this->base_url) {
      return array(false, '', 'This is already a shorten URL!');
    } elseif ( GETHEAD == "yes") {
      if ( ! $this->GetUrlHttpHead($long) ) return array(false, '', 'Can t reach this URL, please try again');
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
    if ( ! $existing_short )
    {
     if ( GETTITLE  == "yes")
     {
      $title = $this->GetUrlHtmlTitle($long);
      $withtitle=' with title :<br /><a> "'.$title.' </a>"';
     }
    }
    switch(true) {
    	case ($short == '' && $existing_short):
    		$short = $existing_short;
        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
        return array(true, $short, 'A short URL already exists for this long URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>');
    		break;
    	case ($short == '' && !$existing_short):
	      $short = $this->getRandomShort();
	      
	      $query = 'INSERT INTO casimir (short_url, long_url, creation_date, title_url ) VALUES ("'.$short.'", "'.$long.'", NOW(), \''. $title."' )";
	      if (mysql_query($query)) {
	        $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
	        return array(true, $short, 'Congratulations, you created this new short URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>'.$withtitle);
	      } else {
	        return array(false, $short, 'Something went wrong: '.mysql_error());
	      }
    		break;
    	case ($short != '' && $existing_long && $long == $existing_long):
    	  $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
        return array(true, $short, 'This short URL already exists and is associated with the same long URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>');
    		break;
    	case ($short != '' && $existing_long && $existing_long != $long):
        return array(false, $short, 'This short URL already exists and is associated with this other long URL:<br /><a href="'.$existing_long.'">'.$existing_long.'</a>');
    		break;
    	case ($short != '' && !$existing_short):
	      $query = 'INSERT INTO casimir (short_url, long_url, creation_date, title_url ) VALUES ("'.$short.'", "'.$long.'", NOW(), \''. $title."' )";
        if (mysql_query($query)) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
	        return array(true, $short, 'Congratulations, you created this new short URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>'.$withtitle);
        } else {
          return array(false, $short, 'Something went wrong: '.mysql_error());
        }
    		break;
    	case ($short != '' && !$existing_long):
    		// Same as previous???
	      $query = 'INSERT INTO casimir (short_url, long_url, creation_date, title_url ) VALUES ("'.$short.'", "'.$long.'", NOW(), \''. $title."' )";
        if (mysql_query($query)) {
          $short_url = $this->base_url.(USE_REWRITE ? '' : '?').$short;
	        return array(true, $short, 'Congratulations, you created this new short URL:<br /><a href="'.$short_url.'">'.$short_url.'</a>'.$withtitle);
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
    $query = "INSERT INTO casimir_stats (short_url, use_date) VALUES ('".trim(mysql_escape_string($short))."', NOW())";
    mysql_query($query);
    $query = "UPDATE casimir SET last_use_date=NOW(), uses=uses+1 WHERE short_url='".trim(mysql_escape_string($short))."'";
    return mysql_query($query);
  }
  	
  function getMostUsedSinceDate($since = '1970-01-01 00:00:01', $nb = 10) {
    $query = "SELECT s.short_url, COUNT(*) AS uses, c.long_url, c.title_url FROM casimir_stats s, casimir c WHERE s.short_url = c.short_url AND use_date >= '".mysql_escape_string($since)."' GROUP BY s.short_url ORDER BY uses DESC LIMIT 0,".max(1,intval($nb));
    if ($res = mysql_query($query)) {
	    $list = '<dl>';
	    while ($url = mysql_fetch_assoc($res)) {
	    	$list .= '<dt><a href="'.$url['short_url'].'" rel="nofollow" >'.$url['short_url'].'</a> visited '.$url['uses'].' time(s)</dt>';
		if ( GETTITLE == "yes" ) $list .= "<dd> with title : ".$url['title_url']." </dd> ";
        $list .= '<dd><a href="'.$url['long_url'].'">'.htmlspecialchars($url['long_url']).'</a></dt>';
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

  // getting the <title> in the HTML head of the URL
  // we only get the first bytes of the file until we find the </title> 
  // to be faster
  function GetUrlHtmlTitle( $longurl ){

   if ( $longurl )
   {
    $url = $longurl;
    $str="";
    $fh = fopen($url, "r");
    $count = 0;
    while ($count < 7500)
    {
     $newstr=fread($fh, 100);  // read 100 more characters, until we find the title
     $str = $str.strtolower($newstr);
     if (strpos($str,"</title>",$count) ) break;
     $count+=100;
     //echo $count."<br />";
    } 

    fclose($fh);
    $str2 = strtolower($str);
    $start = strpos($str2, "<title>")+7;
    $len   = strpos($str2, "</title>") - $start;
    return substr($str, $start, $len);
   }

   else return "";
  }
   

  // getting URL http head and title, we re NOT loading the full page, only head
  // Accepting only existing URLs :p

  function GetUrlHttpHead( $longurl ){

 if ($longurl)
{
  $url = $longurl;

  $ch = curl_init();
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt ($ch, CURLOPT_URL, $url);
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.11) Gecko/20071127 Firefox/2.0.0.11');

  // Only calling the head
  curl_setopt($ch, CURLOPT_HEADER, true); // header will be at output
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD'); // HTTP request is 'HEAD'

  $content = curl_exec ($ch);
  curl_close ($ch);

  //print $content;
  return ($content) ;
 }
else return "";

  }
}
?>

