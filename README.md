xodx
====

pronunciation: [ˈɛksodʊs]

This is an implementation of the basic functionalities of a DSSN Provider, as described in Tramp et al. [An Architecture of a Distributed Semantic Social Network](http://www.semantic-web-journal.net/sites/default/files/swj201_4.pdf):
* [Semantic Pingback](http://aksw.org/Projects/SemanticPingback) for Friending
* [Pubsubhubbub](http://code.google.com/p/pubsubhubbub/) (PuSH) for notification along the edges

It is written in PHP and utilizes the Zend Framework, the [Erfurt Framework](http://erfurt-framework.org/) and [lib-dssn](https://github.com/AKSW/lib-dssn-php).

If you want to read more about xodx take a look at the [wiki](https://github.com/white-gecko/xodx/wiki). In case you see any bugs or have feature-requests please follow the [issue-workflow](https://github.com/white-gecko/xodx/wiki/Issue-Workflow).

Installation
------------
You need a webserver (tested with Apache and nginx but I hope it also runs with lighttd or any other webserver) and a database backend which is supported by Erfurt (Virtuoso and MySQL).
Because this software is written in PHP you'll need php (>= 5.3.7) with the bindings for your webserver or fastcgi, PHP-support for your database (php-odbc or php-mysql) and php-curl.

### Database Connection
Take the prepared `config.ini-dist` file, copy it to `config.ini` and configure it according to your system setup.
If you have an OntoWiki runnnig you can copy the database connection section (`store.*`) into the `config.ini` of xodx.
To import the initial base ontology for Erfurt you have to allow virtuoso to read the xodx directory.
You can configure this by adding the directory to `DirsAllowed` in your `virtuoso.ini` (on debian systems you can find it at `/etc/virtuoso-opensource-6.1/virtuoso.ini`, if you are using the debian packages; or `/var/lib/virtuoso/db/virtuoso.ini`, if you are using the lod2 package or if you have build virtuoso from the source).

### Erfurt, lib-dssn and Saft
Run `make submodules` to clone Erfurt, lib-dssn-php and Saft.

If `make` failes you can try it manually with `git submodule init` and `git submodule update`.

### Zend
Zend is installed with `make zend` or alternatively you have to place a copy of the Zend framework library into `libraries/Zend/` you can do this by doing the following things (replace `${ZENDVERSION}` e.g. with `1.12.0`):

    wget http://packages.zendframework.com/releases/ZendFramework-${ZENDVERSION}/ZendFramework-${ZENDVERSION}-minimal.tar.gz
    tar xzf ZendFramework-${ZENDVERSION}-minimal.tar.gz
    mv ZendFramework-${ZENDVERSION}-minimal/library/Zend libraries
    rm -rf ZendFramework-${ZENDVERSION}-minimal.tar.gz ZendFramework-${ZENDVERSION}-minimal

### JavaScript
In order to get the JavaScript dependencies [twitter bootstrap](http://twitter.github.com/bootstrap/) and [jquery](http://jquery.com/) run:

    make resources

in the xodx root directory (should be the same directory where you found this file).

Code Conventions
----------------
Currently, this project is developed using [OntoWiki's coding standard](https://github.com/AKSW/OntoWiki/wiki/Coding-Standards).

License
-------
Xodx - An implementation of the basic functionalities of a DSSN Provider

Copyright (C) 2013  Natanael Arndt, Norman Radtke

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA, or see
[http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)
for more details.
