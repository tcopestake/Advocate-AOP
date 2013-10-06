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
        "tcopestake/advocate-aop": "1.0.*"
    },

### Manual

Download and extract the files.

If your project doesn't already have a suitable autoloader, you can use the one provided by including src/bootstrap.php:

    include('path/to/src/bootstrap.php');

Note that this autoloader will only work for files within Advocate's src/ and test/ directories.

# Usage

#### Configuration and startup

To initialise the AOP loader, create an instance of `\Advocate\AOP`. Advocate requires three configurations for it to operate properly:

* A valid mapping file. This can be set using `setMapLocation`.
* A valid storage location, for caching recompiled classes. This can be set using `setStorageDirectory`.
* One or more class resolvers, to help Advocate locate classes on disk. A class resolver must implement the `\Advocate\Interfaces\ClassResolver\ClassResolverInterface` interface; the `resolve` method should return the file path if applicable, or `null` / `false` otherwise. See the [Laravel class resolver](https://github.com/tcopestake/Advocate-Laravel) for an example.

Once configured, call the `startUp` method.

Below is a complete example within a test Laravel environment (code placed in global.php)

    (new \Advocate\AOP)
        ->setMapLocation(app_path().'/aop/mapping.php')
        ->setStorageDirectory(storage_path())
        ->addClassResolver(
            (new \Advocate\Extensions\Laravel\ClassResolver)
                ->setComposerLoader(include(base_path().'/vendor/autoload.php'))
        )
        ->startUp();

It is recommended that you call `startUp` only **after** your project's autoloader(s) have already been registered.

#### Mapping

The mapping file should return an array of maps as described in the introduction. For example:

    <?php

    return array(
        array('\Models\User', 'getUsername', '\Aspects\Monitors\User', 'gettingUsername', 'before' => true),
        array('\Models\User', 'getUsername', '\Aspects\Monitors\Generic', 'somethingHappened', 'after' => true),
    );

See [2. Aspect mapping](https://github.com/tcopestake/Advocate-AOP/blob/1.0/doc/02_Aspect_Mapping.md) for a more detailed explanation.