#!/bin/sh
#JPEG - jpegoptim jpegrescan
find . -type f -name '*.jpg' -exec jpegoptim -q --strip-all {} \; -exec chmod 644 {} \;
#find . -type f -name '*.jpg' -exec jpegrescan -s -t -q {} {} \;
find . -type f -name '*.jpg' -exec ~/code/mozjpeg/build/jpegtran -copy none -optimize -progressive {} > {} \; -exec chmod 644 {} \;

#PNG advpng pngout optipng zopflipng
find . -type f -name '*.png' -exec advpng -z4q {} \;
#find . -type f -name '*.png' -exec pngout -yq {} \;
find . -type f -name '*.png' -exec optipng -o7 {} \;
find . -type f -name '*.png' -exec ~/code/zopfli/zopflipng -ym --lossy_transparent --lossy_8bit --splitting=3 {} {} \;

#GIF convert gifsicle
find . -type f -name '*.gif' -exec convert -quiet +set comment -layers optimize {} gif:- | gifsicle > {} \;
