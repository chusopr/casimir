1. Create this table in your MySQL database:

  CREATE TABLE `casimir` (
    `short_url` varchar(50) NOT NULL default '',
    `long_url` text NOT NULL,
    `creation_date` timestamp NOT NULL default '0000-00-00 00:00:00',
    `last_use_date` timestamp NOT NULL default '0000-00-00 00:00:00',
    `uses` int(11) unsigned NOT NULL default '0',
    PRIMARY KEY  (`short_url`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8

2. Copy "casimir-conf.php.example" to "casimir-conf.php" and edit the configuration settings

3. Rename "htaccess" to ".htaccess" according to your settings
