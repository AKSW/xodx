xodx
====

This is an implementation of the basic functionalities of a DSSN Provider:
* Semantic Pingback for Friending
* Pubsubhubbub (PuSH) for notification along the edges

It is written in PHP and utilizes the Zend Framework and the [Erfurt Framework](http://erfurt-framework.org/)

Installation
------------

run `git submodules init` and `git submodules update` to clone Erfurt.

You have to place a copy of the Zend framework library into `libraries/Zend/` you can do this by doing the following things (replace `${ZENDVERSION}` e.g. with `1.11.5`):

    wget http://framework.zend.com/releases/ZendFramework-${ZENDVERSION}/ZendFramework-${ZENDVERSION}-minimal.tar.gz
    tar xzf ZendFramework-${ZENDVERSION}-minimal.tar.gz¶
    mv ZendFramework-${ZENDVERSION}-minimal/library/Zend libraries¶
    rm -rf ZendFramework-${ZENDVERSION}-minimal.tar.gz ZendFramework-${ZENDVERSION}-minimal

You have to add [twitter bootstrap](http://twitter.github.com/bootstrap/) and [jquery](http://jquery.com/) to the `resources` directory.
