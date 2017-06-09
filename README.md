# yii2-certificate-module
Certificates module for Yii2

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist dvizh/yii2-certificate "@dev"
```

or add

```
"dvizh/yii2-certificate": "@dev"
```

to the require section of your composer.json file.

Make migration
```php
php yii migrate --migrationPath=@vendor/dvizh/yii2-certificate/src/migrations/
```

## Usage

You should add module to your config:

```php
'modules' => [
        ...
        'certificate' => [
            'class' => \dvizh\certificate\Module::className(),
            'targetModelList' => [
            //list models for certification
            ]
        ],
    ],
```

It is possible that you have to change minimum stability section of your 
composer.json file to dev
```php
"minimum-stability": "dev",
```
