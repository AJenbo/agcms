#!/bin/sh

# Install the wysiwyg editor
wget --show-progress -q -O ckeditor.zip https://ckeditor.com/cke4/builder/download/007ac3fe0d3df1eb49f7a1a6c5b70551
unzip ckeditor.zip
rm ckeditor.zip
sed -i 's/ckeditor\/plugins\/custimage\/dialogs\/dialogtest.html/\/admin\/explorer\/?return=ckeditor/g' ckeditor/plugins/custimage/dialogs/custimage.js
rm -rf ckeditor/samples/
mv application/javascript/admin/ckeditor/config.js ckeditor/config.js
rm -rf application/javascript/admin/ckeditor/
mv ckeditor application/javascript/admin/

# build frontend
npm install
npm run build

#Install php dependencies
cd application
composer install -o --no-dev
