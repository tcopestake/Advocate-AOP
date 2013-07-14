# Advocate AOP

An experimental agnostic aspect-oriented pseudo-framework.

#### Requirements

* PHP 5.4 (explained below)
* PHP-Parser (see composer.json)

#### Documentation

[See here](https://github.com/tcopestake/Advocate-AOP/tree/master/doc)

## About

This project serves mainly to help me learn more about AOP and to get one perspective on its design principles, potential limitations, etc.

The current version supports some limited functionality; I intend for it to expand as I encounter the need to make changes, plus any outside contributions.

Advocate has been tested during development from within a PHP framework (Laravel 4) and a minimalist flat PHP environment.

### Under the hood

#### Class loading

To support agnostic Composer plug-and-play-ability whilst maintaining the necessary omniscience for fluidity, Advocate will take control of all registered autoloaders and will act as a mediator between them. This allows it to capture and appropriately manage class loading. This should not disrupt any existing autoloader behaviour in any way.

The Advocate mediator supports only partial internal autoloading, for the purpose of mapping and compiling - so your app will still be responsible for all other autoloading, including that of aspect classes.

#### Aspect mapping

Aspects are currently mapped using the aop/mapping.php file, which returns arrays specifying the target class (including namespace), target method, aspect class (including namespace) and aspect method respectively. Multiple aspects and aspect methods can be mapped to any number of targets. An example of this is:

    array('\Models\User', 'getUsername', '\Aspects\Monitors\User', 'gotUsername'),
    array('\Models\User', 'getUsername', '\Aspects\Monitors\Generic', 'somethingHappened'),

Here, both `gotUsername` and `somethingHappened` will be called whenever `getUsername` is called.

#### Compilation

Using [nikic's PHP-Parser](https://github.com/nikic/PHP-Parser), Advocate first searches the target classes for methods with aspects mapped to them. If found, the target class will be recompiled, with the aspect loading and calling code injected where necessary.

Aspect class loading will be added to the constructor. If a constructor isn't found, one will be added. This constructor will be respectful of any parent constructors. One limitation of this is that, as of the current version, errors will result from any `final` constructors within superclasses; `final` constructors in the subclass will not be an issue.

To maintain an environment for the recompiled class that is as authentic as its original, no namespaces, classes, properties or methods are renamed. This means that the use of magic constants will still return expected values. Instead, code that is the target of an aspect will be wrapped in a closure, with the aspect calls following. This is the main reason that PHP 5.4 is a requirement - to retain the availability and behaviour of `$this` within the closures.

Below is an example of a target before compilation:

    <?php

    namespace Models;

    class User
    {
        public function getUsername($user_id, &$username)
        {
            echo 'Getting username for ID: '.$user_id.'<br>';

            $username = 'Demo User';

            return true;
        }
    }

and after:

    <?php namespace Models;

    class User
    {
        protected $aspect_51e2c2fb5dc01277457863_aspects_monitors_user;
        protected $aspect_51e2c2fb5dc7d443951163_aspects_monitors_generic;
        public function getUsername($user_id, &$username)
        {
            $enclosure = function () use($user_id, &$username) {
                echo 'Getting username for ID: ' . $user_id . '<br>';
                $username = 'Demo User';
                return true;
            };
            $return = $enclosure();
            $this->aspect_51e2c2fb5dc01277457863_aspects_monitors_user->gotUsername();
            $this->aspect_51e2c2fb5dc7d443951163_aspects_monitors_generic->somethingHappened();
            return $return;
        }
        public function __construct()
        {
            $this->aspect_51e2c2fb5dc01277457863_aspects_monitors_user = new \Aspects\Monitors\User();
            $this->aspect_51e2c2fb5dc7d443951163_aspects_monitors_generic = new \Aspects\Monitors\Generic();
            $parent = get_parent_class($this);
            if (method_exists($parent, '__construct')) {
                call_user_func_array(array($parent, '__construct'), func_get_args());
            }
        }
    }

This example demonstrates the artificial environment in which the targeted methods are executed, as well as the constructor injected in the absence of any existing.

## Planned improvements

* Passing data between aspects, such as return values and exceptions.
* Possibly rewrite the whole thing, as the code evolved along with my changing ideas of how this should work.

## Known issues

* Conflict between injected and `final` super constructors; future versions will have a fallback for initialization code to be injected preceding the enclosures, if a `final` is identified.
* Inability to differentiate between static and non-static methods; future versions will identify this and adjust the compilation accordingly.

## Unknown issues

There's probably lots of them; feel free to create issues / submit patches.
