xdx
====

This is an implementation of the basic functionalities of a DSSN Provider, as described in Tramp et al. [An Architecture of a Distributed Semantic Social Network](http://www.semantic-web-journal.net/sites/default/files/swj201_4.pdf):
* [Semantic Pingback](http://aksw.org/Projects/SemanticPingback) for Friending
* [Pubsubhubbub](http://code.google.com/p/pubsubhubbub/) (PuSH) for notification along the edges

It is written in PHP and utilizes the Zend Framework, the [Erfurt Framework](http://erfurt-framework.org/) and [lib-dssn](https://github.com/seebi/lib-dssn-php/).

Installation
------------
You need a webserver (tested with Apache, but I hope it also runs with nginx and lighttd) and a database backend which is supported by Erfurt (MySQL and Virtuoso).

Take the prepared `config.ini-dist` file, copy it to `config.ini` and configure it according to your system setup.

### Erfurt and lib-dssn
Run `git submodule init` and `git submodule update` to clone Erfurt and lib-dssn.

### Zend
You have to place a copy of the Zend framework library into `libraries/Zend/` you can do this by doing the following things (replace `${ZENDVERSION}` e.g. with `1.12.0`):

    wget http://packages.zendframework.com/releases/ZendFramework-${ZENDVERSION}/ZendFramework-${ZENDVERSION}-minimal.tar.gz
    tar xzf ZendFramework-${ZENDVERSION}-minimal.tar.gz
    mv ZendFramework-${ZENDVERSION}-minimal/library/Zend libraries
    rm -rf ZendFramework-${ZENDVERSION}-minimal.tar.gz ZendFramework-${ZENDVERSION}-minimal

### JavaScript
In order to get [twitter bootstrap](http://twitter.github.com/bootstrap/) and [jquery](http://jquery.com/) run:

    make resources

in the xodx root directory (should be the same directory where you found this file).

Code Conventions
----------------
Currently, this project is developed using [OntoWiki's coding standard](http://code.google.com/p/ontowiki/wiki/CodingStandard).
