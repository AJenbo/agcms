# AGcms

[![Build Status](https://travis-ci.org/AJenbo/agcms.svg?branch=master)](https://travis-ci.org/AJenbo/agcms)
[![Dependency Status](https://gemnasium.com/badges/github.com/AJenbo/agcms.svg)](https://gemnasium.com/github.com/AJenbo/agcms)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/5d172d5d3df840c4bf958fed492d54b5)](https://www.codacy.com/app/AJenbo/agcms?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=AJenbo/agcms&amp;utm_campaign=Badge_Grade)
[![Maintainability](https://api.codeclimate.com/v1/badges/9fe78b598a206a6162d7/maintainability)](https://codeclimate.com/github/AJenbo/agcms/maintainability)
[![codebeat badge](https://codebeat.co/badges/16e1065d-d41c-4082-a86c-22af842274f1)](https://codebeat.co/projects/github-com-ajenbo-agcms-master)
[![BCH compliance](https://bettercodehub.com/edge/badge/AJenbo/agcms?branch=master)](https://bettercodehub.com/)
[![Coverage Status](https://coveralls.io/repos/github/AJenbo/agcms/badge.svg?branch=master)](https://coveralls.io/github/AJenbo/agcms?branch=master)

AGcms is a simple CMS that I build around 2004, since it still have active userse I try to maintain the code base and use it to experiment with differnet tools. The maintanace mostly revolve around keeping the back end in a decent shape code wise.

## Development setup

**PHP**

The code is found in application/inc. It is structured to be similar to [Laravel](https://laravel.com/), but with a some what limited scope.

**JavaScript**

The source is found in source/javascript, it is transpiled using webpack meaning you can write ES6 and having it still work on older browseres that only support ES5. To run the transpiler execute the following command:
```bash
npm run build
```

**Prerequisites**
* [Composer](https://getcomposer.org/download/)
* [NPM](https://www.npmjs.com/get-npm)
* [NodeJS](https://nodejs.org/en/)
* [Docker Compose](https://docs.docker.com/compose/)

On Ubuntu this can be install using the following command:
```bash
sudo apt install composer npm nodejs-legacy docker.io docker-compose
sudo adduser yourussername docker
```
Then log out and back in.

build.sh also depends on wget and unzip

**Install dependencies**

build.sh install most dependencies, except for php development liberies
```bash
./build.sh
cd application
composer install
```

**Running the project**

The project comes with a docker setup that will run an NginX server on port 80 and MySQL on 3306 so thease ports neads to be avalible. To start it simply run:
```bash
docker-compose up
```

If you want to setup a server manually you need to point it to the application folder, you will find the needed sql files for the database in the source folder.

**Running tests**

You can run the unit tests via the following command from the project root:
```bash
php application/vendor/bin/phpunit
```

## Preparing a release
Run the build.sh script
```bash
./build.sh
```
Upload the content of the application folder to your webserver.

Afterwareds you will need to run the following command to return to develop mode:
```bash
cd application
composer install
```

## Security Vulnerabilities

If you discover a security vulnerability within AGcms, please create an issue on [github](https://github.com/AJenbo/agcms/issues). All security vulnerabilities will be promptly addressed.

## License

AGcms is open-sourced software licensed under the [GPL-2.0 license](https://opensource.org/licenses/GPL-2.0). As such you are free to base your site on it. If you decide to do so I would love to hear about it :)
