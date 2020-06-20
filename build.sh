#!/bin/bash

composer install
rm -rf extras/phpmailer xlsxwriter.class.php
cp -R vendor/phpmailer/phpmailer extras/
rm -rf extras/phpmailer/examples
cp -R vendor/mk-j/php_xlsxwriter/xlsxwriter.class.php extras/
rm -rf vendor composer.lock

npm install
cp node_modules/bootbox/bootbox.min.js view/js/
cp node_modules/bootstrap/dist/css/bootstrap.min.css view/css/
cp node_modules/bootstrap/dist/fonts/* view/fonts/
cp node_modules/bootstrap/dist/js/bootstrap.min.js view/js/
cp node_modules/bootswatch/cosmo/bootstrap.min.css view/css/bootstrap-cosmo.min.css
cp node_modules/bootswatch/darkly/bootstrap.min.css view/css/bootstrap-darkly.min.css
cp node_modules/bootswatch/flatly/bootstrap.min.css view/css/bootstrap-flatly.min.css
cp node_modules/bootswatch/lumen/bootstrap.min.css view/css/bootstrap-lumen.min.css
cp node_modules/bootswatch/paper/bootstrap.min.css view/css/bootstrap-paper.min.css
cp node_modules/bootswatch/sandstone/bootstrap.min.css view/css/bootstrap-sandstone.min.css
cp node_modules/bootswatch/simplex/bootstrap.min.css view/css/bootstrap-simplex.min.css
cp node_modules/bootswatch/spacelab/bootstrap.min.css view/css/bootstrap-spacelab.min.css
cp node_modules/bootswatch/united/bootstrap.min.css view/css/bootstrap-united.min.css
cp node_modules/bootswatch/yeti/bootstrap.min.css view/css/bootstrap-yeti.min.css
cp node_modules/font-awesome/css/* view/css/
cp node_modules/font-awesome/fonts/* view/fonts/
cp node_modules/jquery/dist/jquery.min.js view/js/
rm -rf node_modules package-lock.json