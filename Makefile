# vim: set ai ts=4 sw=4 et!:
TW_BOOTSTRAP_SRC='http://twitter.github.com/bootstrap/assets/bootstrap.zip'
JQUERY_SRC='http://code.jquery.com/jquery.js'
JQUERY_MIN_SRC='http://code.jquery.com/jquery.min.js'

ERFURT_SRC='https://github.com/AKSW/Erfurt/archive/develop.tar.gz'
DSSN_SRC='https://github.com/AKSW/lib-dssn-php/archive/master.tar.gz'

ZENDVERSION=1.11.5

submodules: # read-only
	git submodule init
	git config submodule.libraries/Erfurt.url "git://github.com/AKSW/Erfurt.git"
	git config submodule.libraries/lib-dssn-php.url "git://github.com/AKSW/lib-dssn-php.git"
	git submodule update

submodules-developer: # read-write
	git submodule init
	git config submodule.libraries/Erfurt.url "git@github.com:AKSW/Erfurt.git"
	git config submodule.libraries/lib-dssn-php.url "git@github.com:AKSW/lib-dssn-php.git"
	git submodule update

submodules-zip:
	rm -rf libraries/Erfurt
	curl -# ${ERFURT_SRC} -o erfurt.tar.gz || wget ${ERFURT_SRC} -O erfurt.tar.gz
	tar xzf erfurt.tar.gz
	mv erfurt libraries/Erfurt
	rm erfurt.tar.gz
	rm -rf libraries/lib-dssn-php
	curl -# ${DSSN_SRC} -o dssn.tar.gz || wget ${DSSN_SRC} -O dssn.tar.gz
	tar xzf dssn.tar.gz
	mv lib-dssn-php libraries/lib-dssn-php
	rm dssn.tar.gz

libraries: zend resources

resources: twbootstrap jquery

twbootstrap:
	rm -rf resources/bootstrap
	curl -# ${TW_BOOTSTRAP_SRC} -o bootstrap.zip || wget ${TW_BOOTSTRAP_SRC} -O bootstrap.zip
	unzip bootstrap.zip
	mv bootstrap resources/bootstrap
	rm bootstrap.zip

jquery:
	curl -# -o jquery.js ${JQUERY_MIN_SRC} || wget ${JQUERY_MIN_SRC} -O jquery.js
	mkdir -p resources/jquery
	mv jquery.js resources/jquery/jquery.js

zend:
	rm -rf libraries/Zend
	curl -# -O http://packages.zendframework.com/releases/ZendFramework-${ZENDVERSION}/ZendFramework-${ZENDVERSION}-minimal.tar.gz || wget http://packages.zendframework.com/releases/ZendFramework-${ZENDVERSION}/ZendFramework-${ZENDVERSION}-minimal.tar.gz
	tar xzf ZendFramework-${ZENDVERSION}-minimal.tar.gz
	mv ZendFramework-${ZENDVERSION}-minimal/library/Zend libraries
	rm -rf ZendFramework-${ZENDVERSION}-minimal.tar.gz ZendFramework-${ZENDVERSION}-minimal

