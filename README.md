# installing with composer from this particular fork
### 1. Add the following below your name/description:
```angular2html
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mario7dujmovic/routeros-api-1.git"
        }
    ],
```
### 2. Require it in your `require` segment, e.g.:
```angular2html
    "require": {
        (...)
        "ben-menking/routeros-api": "dev-stable",
        (...)
    }
```
### 3. run `composer install`

# routeros-api
Client API for RouterOS/Mikrotik

This class was originally written by Denis Basta and updated by several contributors.  It aims to give a simple interface to the RouterOS API in PHP.

Mikrotik Wiki page at http://wiki.mikrotik.com/wiki/API_PHP_class

## Contributors (before moving to Git)
* Nick Barnes
* Ben Menking (ben [at] infotechsc [dot] com)
* Jeremy Jefferson (http://jeremyj.com)
* Cristian Deluxe (djcristiandeluxe [at] gmail [dot] com)
* Mikhail Moskalev (mmv.rus [at] gmail [dot] com)

## Changelog

Please see git logs.  Version 1.0 through current version have been preserved in this Git repo.

