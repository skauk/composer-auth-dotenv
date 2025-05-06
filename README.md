# composer-auth-dotenv

[![Packagist version](https://img.shields.io/packagist/v/rcknr/composer-auth-dotenv.svg?maxAge=3600)](https://packagist.org/packages/rcknr/composer-auth-dotenv)
[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/rcknr/composer-auth-dotenv/continuous-integration.yml?branch=main)](https://github.com/rcknr/composer-auth-dotenv/actions)
[![Coverage Status](https://coveralls.io/repos/github/rcknr/composer-auth-dotenv/badge.svg?branch=main)](https://coveralls.io/repos/github/rcknr/composer-auth-dotenv/badge.svg?branch=main)
[![Packagist downloads](https://img.shields.io/packagist/dt/rcknr/composer-auth-dotenv.svg?maxAge=3600)](https://packagist.org/packages/rcknr/composer-auth-dotenv)

This [Composer](https://getcomposer.org/) plugin lets you store credentials for private packages in a `.env` file, using the same format as the `COMPOSER_AUTH` environment variable. This approach avoids using `auth.json`, keeps credentials out of version control, and makes them easier to manage and encrypt alongside other environment-specific configuration. 
This repository is inspired by [private-composer-installer](https://github.com/ffraenz/private-composer-installer).

## Motivation

In Laravel ecosystem there are many commercially distributed packages that require authentication to be installed.
The usual way to authenticate is to use the `auth.json` file, which is then ignored (or not) by version control.
It is more practical, however, to be able to store credentials in a `.env` file, which is already used for other environment-specific configuration.
The `.env` file can be encrypted and decrypted using the `php artisan env:encrypt` and `php artisan env:decrypt` commands. 
The encryption key can be shared with team members to securely grant access to all necessary project secrets.
This _might_ make it easier and safer to manage composer credentials in your development team.

## Setup

- Add the desired private package to the `repositories` section inside `composer.json`.
Find more about Composer repositories in the [Composer documentation](https://getcomposer.org/doc/05-repositories.md#repositories).
- Configure authentication using `composer config`. This will create an `auth.json` file. Different supported options are described in the [Composer documentation](https://getcomposer.org/doc/articles/authentication-for-private-packages.md)
- Convert `auth.json` content to COMPOSER_AUTH environment variable format and append it to your `.env` file:

```
echo -n "COMPOSER_AUTH=" >> .env && cat auth.json | tr -d '\n[:space:]' >> .env
```

## Configuration

The configuration options listed below may be added to the `extra` section of `composer.json` like so:

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
