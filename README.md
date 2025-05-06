# composer-auth-dotenv

[![Packagist version](https://img.shields.io/packagist/v/rcknr/composer-auth-dotenv.svg?maxAge=3600)](https://packagist.org/packages/rcknr/private-composer-installer)
[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/rcknr/private-composer-installer/continuous-integration.yml?branch=main)](https://github.com/rcknr/private-composer-installer/actions)
[![Coverage Status](https://coveralls.io/repos/github/rcknr/private-composer-installer/badge.svg?branch=main)](https://coveralls.io/repos/github/rcknr/private-composer-installer/badge.svg?branch=main)
[![Packagist downloads](https://img.shields.io/packagist/dt/rcknr/composer-auth-dotenv.svg?maxAge=3600)](https://packagist.org/packages/rcknr/private-composer-installer)

This is a [Composer](https://getcomposer.org/) plugin offering a way to reference private package URLs within `composer.json` and `composer.lock`. It outsources sensitive dist URL parts (license keys, tokens) into environment variables or a `.env` file typically ignored by version control. This is especially useful when you can't use [Private Packagist](https://packagist.com/) or [Basic HTTP Auth](https://getcomposer.org/doc/articles/authentication-for-private-packages.md#http-basic) because the source of a package is not in your control. This repository is inspired by [acf-pro-installer](https://github.com/PhilippBaschke/acf-pro-installer).

## Motivation

Include private packages in your projects which use `.env` files for configuration. 

[//]: # (- If an environment variable is not available for the given placeholder the plugin trys to read it from the `.env` file in the working directory or in one of the parent directories. The `.env` file gets parsed by [vlucas/phpdotenv]&#40;https://github.com/vlucas/phpdotenv&#41;.)

## Setup

- Add the desired private package to the `repositories` section inside `composer.json`.
Find more about Composer repositories in the [Composer documentation](https://getcomposer.org/doc/05-repositories.md#repositories).
- Configure authentication using `composer config`. This will create an `auth.json` file. Different supported options are described in the [Composer documentation](https://getcomposer.org/doc/articles/authentication-for-private-packages.md)
- Convert `auth.json` content to COMPOSER_AUTH environment variable format and append it to your `.env` file:

```
echo -n "COMPOSER_AUTH=" >> .env && cat auth.json | tr -d '\n[:space:]' >> .env
```

## Configuration

The configuration options listed below may be added to the `"extra"` section in `composer.json` like so:

```json
{
  "name": "...",
  "description": "...",
  "require": {
  },
  "extra": {
    "composer-auth-dotenv": {
      "dotenv-path": ".",
      "dotenv-name": ".env"
    }
  }
}
```

### dotenv-path

Dotenv file directory relative to the root path (where `composer.json` is located).
By default, dotenv files are expected to be in the root folder.

### dotenv-name

Dotenv file name. Defaults to `.env`.
