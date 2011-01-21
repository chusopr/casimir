1. Create these tables in your MySQL database:

  CREATE TABLE `casimir` (
    `short_url` varchar(50) NOT NULL DEFAULT '',
    `long_url` text NOT NULL,
    `creation_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `last_use_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `uses` int(11) unsigned NOT NULL DEFAULT '0',
    `title_url` varchar(128) NOT NULL DEFAULT 'No title defined for this url',
    PRIMARY KEY (`short_url`),
    FULLTEXT KEY `title_url` (`title_url`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

  CREATE TABLE `casimir_stats` (
    `short_url` varchar(50) NOT NULL default '',
    `use_date` timestamp NOT NULL default '0000-00-00 00:00:00'
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

2. Copy "user/casimir-conf.php.example" to "user/casimir-conf.php" and edit the configuration settings

3. Rename "htaccess" to ".htaccess" according to your settings

4. Optionnaly, you can add
   - a "user/footer.php" script that will be added after Casimir's footer
   - a "user/screen.css" stylesheet that will be loaded after Casimir's one
