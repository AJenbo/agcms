cd /domains/cms
xgettext --language=PHP --default-domain=agcms --from-code=UTF-8 *.php
cd bestilling
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php
cd ../betaling
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php
cd ../krav
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php
cd ../inc
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php
cd ../krak
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php
cd ../nybruger
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php
cd ../admin
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php
cd inc
xgettext --join-existing --language=PHP --default-domain=../../agcms --from-code=UTF-8 *.php
cd ../../upload
xgettext --join-existing --language=PHP --default-domain=../agcms --from-code=UTF-8 *.php

cd /domains/sites/jof/post
xgettext --language=PHP --default-domain=post --from-code=UTF-8 *.php

cd /domains/sites/jof/pnl
xgettext --language=PHP --default-domain=pnl --from-code=UTF-8 *.php
cd ~
