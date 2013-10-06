# Installation

This package can be installed manually or via Composer.

### Composer

Add the following to your project's composer.json:

    "repositories": [
        {
            "type": "vcs",
            "url":  "https://github.com/tcopestake/Advocate-AOP"
        }
    ],

and:

    "require": {
        "tcopestake/advocate-aop": "v0.5-maint"
    },

### Manual

Download and extract the files.

If your project doesn't already have a suitable autoloader, you can use the one provided by including src/bootstrap.php:

    include('path/to/src/bootstrap.php');

Note that this autoloader will only work for files within Advocate's src/ and test/ directories.

# Usage

To initialise the AOP loader, create an instance of `\Advocate\AOP` and call the `init` method.

    $aop = new \Advocate\AOP('path/to/app');
    $aop->init();

It is recommended that you do this **after** your projects' autoloader(s) have already been registered.

The parameter passed to the constructor is your "working directory" i.e. where your class files, aspects and maps will reside. In a typical PHP framework, for example, this may be your "app" directory.

The AOP mapper will look for your maps in "aop/mapping.php" within the specified working directory.

This file should return an array of maps as described in the introduction. For example:

    <?php

    return array(
        array('\Models\User', 'getUsername', '\Aspects\Monitors\User', 'gotUsername'),
        array('\Models\User', 'getUsername', '\Aspects\Monitors\Generic', 'somethingHappened'),
    );
