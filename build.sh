#!/bin/sh

# Install the wysiwyg editor
wget --show-progress -q -O ckeditor.zip http://ckeditor.com/builder/download/4ef93c2297099222b5d2ba696dceaafa
unzip ckeditor.zip
rm ckeditor.zip
sed -i 's/ckeditor\/plugins\/custimage\/dialogs\/dialogtest.html/\/admin\/explorer\/?return=ckeditor/g' ckeditor/plugins/custimage/dialogs/custimage.js
rm -rf ckeditor/samples/
mv application/javascript/admin/ckeditor/config.js ckeditor/config.js
rm -rf application/javascript/admin/ckeditor/
mv ckeditor application/javascript/admin/

#Install php dependencies
cd application
composer install -o --no-dev
