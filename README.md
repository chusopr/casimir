# Cas.im/ir

## Yet Another URL Shortener (or Shrinker)

Cas.im/ir is inspired by — and tries to find its way between — both [lilURL](http://lilurl.sourceforge.net/) and [xav.cc](http://xav.cc/) 's [sfShortUrlPlugin](http://www.symfony-project.org/plugins/sfShortUrlPlugin), the first lacking some essential features and the later needing a whole [symfony](http://www.symfony-project.org/) environment, as the location implies.

Cas.im/ir is both available online at [cas.im](http://cas.im/) and as a [download](https://github.com/chusopr/casimir/releases) for self hosting.

It only needs PHP and MySQL to run. Just install it, following the instructions in the readme file, and enjoy it!

The development is [hosted on GitHub](http://github.com/chusopr/casimir), so you can fork it and help me enhance it at will!

*Disclaimer:* I didn't try to follow any PHP golden development rules, it just had to work as I needed.

Follow [Cas.im/ir on Twitter](http://twitter.com/cas_im) to be notified of new releases or ask for help.

## License

See the included [LICENSE.md](https://github.com/chusopr/casimir/blob/master/LICENSE.md) file.

## Installation

### Database creation

Create these tables in your MySQL database:

    CREATE TABLE `casimir` (
      `short_url` varchar(50) NOT NULL DEFAULT '',
      `long_url` text NOT NULL,
      `creation_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
      `last_use_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
      `uses` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`short_url`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
  
    CREATE TABLE `casimir_stats` (
      `short_url` varchar(50) NOT NULL default '',
      `use_date` timestamp NOT NULL default '0000-00-00 00:00:00'
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

### Configuration

Copy `user/casimir-conf.php.example` to `user/casimir-conf.php` and edit the configuration settings.

Rename `htaccess` to `.htaccess` according to your settings.

### Personalization

Optionnaly, you can add
- a `user/footer.php` script that will be added after Cas.im/ir's footer
- a `user/screen.css` stylesheet that will be loaded after Cas.im/ir's one
