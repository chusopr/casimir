# Cas.im/ir v1.3 (2011/01/17)

Enhancements and fixes by [Neofutur](https://github.com/neofutur):

If you don't have it yet, add this field to your "casimir" table:

    ALTER TABLE `casimir` ADD `title_url` VARCHAR(128) NOT NULL DEFAULT 'No title defined for this url', ADD FULLTEXT (`title_url`)

# Cas.im/ir v1.2 (2010/01/20)

- FIXED: handle empty short URL creation with bookmarklet
- FIXED: readme.txt

# Cas.im/ir v1.1 (2010/01/05)

- NEW: daily, weekly, and monthly statistics
- FIXED: access_key added to the bookmarklet

If you don't have it yet, create this table in your MySQL database:

    CREATE TABLE `casimir_stats` (
      `short_url` varchar(50) NOT NULL default '',
      `use_date` timestamp NOT NULL default '0000-00-00 00:00:00'
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8

