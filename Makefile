# vim: set ai ts=4 sw=4 et!:
TW_BOOTSTRAP_SRC='http://twitter.github.com/bootstrap/assets/bootstrap.zip'
JQUERY_SRC='http://code.jquery.com/jquery.js'
JQUERY_MIN_SRC='http://code.jquery.com/jquery.min.js'

ERFURT_SRC='https://github.com/AKSW/Erfurt/archive/develop.tar.gz'
DSSN_SRC='https://github.com/AKSW/lib-dssn-php/archive/master.tar.gz'
SAFT_SRC='https://github.com/white-gecko/Saft/archive/master.tar.gz'

ZENDVERSION=1.12.0

default:
	@echo "You might want to run:"
	@echo ""
	@echo "	make install        to get all dependencies through github"
	@echo "	make install-fb     to get all dependencies from ZIP files (faster and good for non developing mashines like a FreedomBox)"
	@echo "	make install-dev    to get all dependencies (if you have write permission)"

help:
	@echo "You have following options:"
	@echo ""
	@echo "basic:"
	@echo "	make install        to get all dependencies through github"
	@echo "	make install-fb     to get all dependencies from ZIP files (faster and good for non developing mashines like a FreedomBox)"
	@echo "	make install-dev    to get all dependencies (if you have write permission)"
	@echo ""
	@echo "advanced:"
	@echo "	make libraries      to get zend and the JavaScript resources"
	@echo "	make resources      to get the JavaScript resources"
	@echo "	make zend           to get the zend (needed for Erfurt)"
	@echo "	make submodules     to get the git submodules"
	@echo "	make submodules-dev to get the git submodules (if you have write permission)"
	@echo "	make submodules-zip to get the git submodules as ZIP (faster)"
	@echo ""
	@echo "pro:"
	@echo "	for all other options you have to read the Makefile ..."

info:
	less README.md

# Default installation for external users
install: libraries submodules

# Installation for developers with writer permission
install-dev: libraries submodules-dev

# Installation without git remotes, just zip files
install-fb: libraries submodules-zip

# shortcut for the non submodule dependencies
libraries: zend resources

# shortcut for javascript libraries
resources: twbootstrap jquery

submodules: # read-only
	git submodule init
	git config submodule.libraries/Erfurt.url "git://github.com/AKSW/Erfurt.git"
	git config submodule.libraries/lib-dssn-php.url "git://github.com/AKSW/lib-dssn-php.git"
	git config submodule.libraries/Saft.url "git://github.com/white-gecko/Saft.git"
	git submodule update

submodules-dev: # read-write
	git submodule init
	git config submodule.libraries/Erfurt.url "git@github.com:AKSW/Erfurt.git"
	git config submodule.libraries/lib-dssn-php.url "git@github.com:AKSW/lib-dssn-php.git"
	git config submodule.libraries/Saft.url "git@github.com:white-gecko/Saft.git"
	git submodule update

submodules-zip: erfurt-zip dssn-zip saft-zip

erfurt-zip:
	rm -rf libraries/Erfurt
	curl -# ${ERFURT_SRC} -o erfurt.tar.gz || wget ${ERFURT_SRC} -O erfurt.tar.gz
	tar xzf erfurt.tar.gz
	mv Erfurt-develop libraries/Erfurt
	rm erfurt.tar.gz

dssn-zip:
	rm -rf libraries/lib-dssn-php
	curl -# ${DSSN_SRC} -o dssn.tar.gz || wget ${DSSN_SRC} -O dssn.tar.gz
	tar xzf dssn.tar.gz
	mv lib-dssn-php-master libraries/lib-dssn-php
	rm dssn.tar.gz

saft-zip:
	rm -rf libraries/Saft
	curl -# ${SAFT_SRC} -o saft.tar.gz || wget ${SAFT_SRC} -O saft.tar.gz
	tar xzf saft.tar.gz
	mv Saft-master libraries/Saft
	rm saft.tar.gz

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
	curl -# -O https://packages.zendframework.com/releases/ZendFramework-${ZENDVERSION}/ZendFramework-${ZENDVERSION}-minimal.tar.gz || wget https://packages.zendframework.com/releases/ZendFramework-${ZENDVERSION}/ZendFramework-${ZENDVERSION}-minimal.tar.gz
	tar xzf ZendFramework-${ZENDVERSION}-minimal.tar.gz
	mv ZendFramework-${ZENDVERSION}-minimal/library/Zend libraries
	rm -rf ZendFramework-${ZENDVERSION}-minimal.tar.gz ZendFramework-${ZENDVERSION}-minimal

