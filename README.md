PHPUnit TeamCity TestListener
========================================
[![Latest Stable Version](https://poser.pugx.org/munkie/phpunit-teamcity-testlistener/v/stable)](https://packagist.org/packages/munkie/phpunit-teamcity-testlistener)
[![Total Downloads](https://poser.pugx.org/munkie/phpunit-teamcity-testlistener/downloads)](https://packagist.org/packages/munkie/phpunit-teamcity-testlistener)
[![License](https://poser.pugx.org/munkie/phpunit-teamcity-testlistener/license)](https://packagist.org/packages/munkie/phpunit-teamcity-testlistener)
[![Build Status](https://travis-ci.org/munkie/phpunit-teamcity-testlistener.svg)](https://travis-ci.org/munkie/phpunit-teamcity-testlistener)

`PHPUnit TeamCity TestListener` is extension for integration [PHPUnit](http://phpunit.de) and [TeamCity](http://www.jetbrains.com/teamcity/) continious integration server. Based on PHPUnit's TestListener feature and TeamCity's Service Messages providing fast and easy test reporting during build process.

Requirements
------------

* PHPUnit 3.7
* TeamCity 7.0+

Installation
------------

Simply add a dependency on `munkie/phpunit-teamcity-testlistener` to your project's `composer.json` file if you use [Composer](http://getcomposer.org/) to manage the dependencies of your project.
Here is a minimal example of a `composer.json` file:

```json
{
    "require-dev": {
        "munkie/phpunit-teamcity-testlistener": "dev-master"
    }
}
```

Documentation
-------------

Add Build Step in TeamCity:

```sh
phpunit --printer PHPUnit\\TeamCity\\TestListener
```

Press "Run..." button in TeamCity.
Now build will display executed tests in realtime in "Overview" screen