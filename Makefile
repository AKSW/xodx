# vim: set ai ts=4 sw=4 et!:
TW_BOOTSTRAP_SRC='http://twitter.github.com/bootstrap/assets/bootstrap.zip'
JQUERY_SRC='code.jquery.com/jquery.js'
JQUERY_MIN_SRC='code.jquery.com/jquery.min.js'

resources: twbootstrap jquery

twbootstrap: rmtwbootstrap
	cd resources
	curl -# ${TW_BOOTSTRAP_SRC} -o bootstrap.zip || wget ${TW_BOOTSTRAP_SRC} -O bootstrap.zip
	unzip bootstrap.zip
	mv bootstrap resources/bootstrap
	rm bootstrap.zip

rmtwbootstrap:
	rm -rf resources/bootstrap

jquery:
	curl -# -o jquery.js ${JQUERY_MIN_SRC} || wget ${JQUERY_MIN_SRC} -O jquery.js
	mv jquery.js resources/jquery/jquery.js
