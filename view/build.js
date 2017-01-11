var buildify = require('buildify'),
    nodePath = 'node_modules/';

buildify()
    .concat([
        nodePath + 'jquery/dist/jquery.js',
        nodePath + 'bootstrap-sass/assets/javascripts/bootstrap.js',
        nodePath + 'bootstrap-datepicker/dist/js/bootstrap-datepicker.js',
        nodePath + 'devbridge-autocomplete/dist/jquery.autocomplete.min.js',
        nodePath + 'jquery-validation/dist/jquery.validate.js',
        nodePath + 'jquery-ui/ui/widget.js',
        'js/base.js'
    ])
    .uglify()
    .save('js/build.min.js');
