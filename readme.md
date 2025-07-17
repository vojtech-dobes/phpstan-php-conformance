# PHPStan extension for [Vojtechdobes\Conformance](https://github.com/vojtech-dobes/php-conformance)

![Checks](https://github.com/vojtech-dobes/phpstan-php-conformance/actions/workflows/checks.yml/badge.svg?branch=master&event=push)

This PHPStan extension is recommended to use with [`vojtech-dobes/php-conformance`](https://github.com/vojtech-dobes/php-conformance) validation & normalization library. It lets PHPStan understand what exact data structure does given constraint lead to.



## Installation

To install the latest version, run the following command:

```
composer require vojtech-dobes/phpstan-php-conformance
```

If you're not using [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer), then add this to your PHPStan config:

```neon
includes:
  - vendor/vojtech-dobes/phpstan-php-conformance/extension.neon
```

No further configuration needed.
