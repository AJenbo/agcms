# AGcms

[![Test](https://github.com/AJenbo/agcms/actions/workflows/test.yml/badge.svg)](https://github.com/AJenbo/agcms/actions/workflows/test.yml)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/5d172d5d3df840c4bf958fed492d54b5)](https://www.codacy.com/gh/AJenbo/agcms/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=AJenbo/agcms&amp;utm_campaign=Badge_Grade)
[![Maintainability](https://api.codeclimate.com/v1/badges/9fe78b598a206a6162d7/maintainability)](https://codeclimate.com/github/AJenbo/agcms/maintainability)
[![codebeat badge](https://codebeat.co/badges/16e1065d-d41c-4082-a86c-22af842274f1)](https://codebeat.co/projects/github-com-ajenbo-agcms-master)
[![codecov](https://codecov.io/gh/AJenbo/agcms/branch/master/graph/badge.svg?token=hFHGd8QVSo)](https://codecov.io/gh/AJenbo/agcms)

AGcms is a simple CMS that I built around 2004, since it still has active users, I try to maintain the code base and use it to experiment with differnet tools. The maintenance mostly revolve around keeping the backend in decent shape, code wise.

## Development setup

### PHP

The PHP source is found in application/inc
It's structured to be similar to [Laravel](https://laravel.com/)

### JavaScript

The JavaScript source is found in source/javascript
It's transpiled using webpack, meaning you can write ES6 and having it still work on older browsers that only support ES5

### Prerequisites

- [Composer](https://getcomposer.org/doc/00-intro.md)
- [Docker Compose](https://docs.docker.com/get-docker/)
- [NodeJS](https://nodejs.org/en/download/package-manager/)
- [PHP](https://www.php.net/manual/en/install.php)

It's recommended to follow the install guides for each prerequisite linked above
`build.sh` also depends on wget and unzip

### Install dependencies

Executing `build.sh` will install the project dependencies, except for php development dependencies

#### Install development dependencies

Run the following three commands if you would like to install all dependencies

```bash
./build.sh
cd application
composer install
```

### Running the project

The project comes with a docker-compose.yml that will run an nginx server on port 80 and MySQL on 3306 by default
To start it simply run:

```bash
docker compose up -d
```

If you want to setup a server manually you need to point it to the application folder, you will find the needed sql files for the database in the source folder.

### Running tests

You can run the PHP unit tests via the following command from the project root

```bash
php application/vendor/bin/phpunit
```

## Preparing a release

Run the `build.sh` script
Upload the content of the application folder to your webserver

## Security Vulnerabilities

If you discover a security vulnerability within AGcms, please create an issue on [github](https://github.com/AJenbo/agcms/issues)
All security vulnerabilities will be promptly addressed

## License

AGcms is open-sourced software licensed under the [GPL-2.0 license](https://opensource.org/licenses/GPL-2.0)
As such you are free to base your site on it
If you decide to do so I would love to hear about it :)
