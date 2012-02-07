# Cas.im/ir v2.x

## Cas.im/ir v2.0 (2012/02/07)

Diet. KISS.

If you upgraded from a previous release, you can remove the `title_url` field from the `casimir` table:
    ALTER TABLE `casimir` DROP COLUMN `title_url`

- FIXED: PHP 5.3 compatibility (ereg deprecated)
  cf https://github.com/neofutur/gwgd/commit/bd9e52e9f2a983a779bd10f33c5b780d45883158
- FIXED: no longer fails miserably when there is no configuration file yet

# Cas.im/ir v1.x

## Cas.im/ir v1.3 (2011/01/17)

Enhancements and fixes by [Neofutur](https://github.com/neofutur):

If you don't have it yet, add this field to your "casimir" table:
    ALTER TABLE `casimir` ADD `title_url` VARCHAR(128) NOT NULL DEFAULT 'No title defined for this url', ADD FULLTEXT (`title_url`)

## Cas.im/ir v1.2 (2010/01/20)

- FIXED: handle empty short URL creation with bookmarklet
- FIXED: readme.txt

## Cas.im/ir v1.1 (2010/01/05)

- NEW: daily, weekly, and monthly statistics
- FIXED: access_key added to the bookmarklet

If you don't have it yet, create this table in your MySQL database:

    CREATE TABLE `casimir_stats` (
      `short_url` varchar(50) NOT NULL default '',
      `use_date` timestamp NOT NULL default '0000-00-00 00:00:00'
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8

