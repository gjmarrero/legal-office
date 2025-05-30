# PDF version converter 
PHP library for converting the version of PDF files (for compatibility purposes).

[![Latest Stable Version](http://poser.pugx.org/anton-am/pdf-version-converter/v)](https://packagist.org/packages/anton-am/pdf-version-converter) [![Total Downloads](http://poser.pugx.org/anton-am/pdf-version-converter/downloads)](https://packagist.org/packages/anton-am/pdf-version-converter) [![Latest Unstable Version](http://poser.pugx.org/anton-am/pdf-version-converter/v/unstable)](https://packagist.org/packages/anton-am/pdf-version-converter) [![License](http://poser.pugx.org/anton-am/pdf-version-converter/license)](https://packagist.org/packages/anton-am/pdf-version-converter) [![PHP Version Require](http://poser.pugx.org/anton-am/pdf-version-converter/require/php)](https://packagist.org/packages/anton-am/pdf-version-converter)

## Requirements

- PHP 7.2.5+
- Ghostscript (gs command on Linux)

## Installation

Run `php composer.phar require xthiago/pdf-version-converter dev-master` or add the follow lines to composer and run `composer install`:

```
{
    "require": {
        "anton-am/pdf-version-converter": "^1.0.6"
    }
}
```

## Usage

Guessing a version of PDF File:

```php
<?php

// import the composer autoloader
require_once __DIR__.'/vendor/autoload.php'; 

// import the namespaces
use AntonAm\PDFVersionConverter\Guesser\RegexGuesser;
// [..]

$guesser = new RegexGuesser();
echo $guesser->guess('/path/to/my/file.pdf'); // will print something like '1.4'
```

Converting file to a new PDF version:

```php
<?php

// import the composer autoloader
require_once __DIR__.'/vendor/autoload.php'; 

// import the namespaces
use Symfony\Component\Filesystem\Filesystem,
    AntonAm\PDFVersionConverter\Converter\GhostscriptConverterCommand,
    AntonAm\PDFVersionConverter\Converter\GhostscriptConverter;

// [..]

$command = new GhostscriptConverterCommand();
$filesystem = new Filesystem();

$converter = new GhostscriptConverter($command, $filesystem);
$converter->convert('/path/to/my/file.pdf', '1.4');
```

## Contributing

Is really simple add new implementation of guesser or converter, just implement `GuessInterface` or `ConverterInterface`.

## Running unit tests

Run `phpunit -c tests`.
