# Aspect mapping

As demonstrated in **Installation and Usage**, example syntax for the mapping file is:

    <?php

    return array(
        array(
            '\Models\User', // Target class
            'getUsername', // Target method
            '\Aspects\Monitors\User', // Aspect class
            'gettingUsername', // Aspect method
            'before' => true // Join before true/false
            // 'after' => true // Join after true/false
        ),
        array(
            '\Models\User',
            'getUsername',
            '\Aspects\Monitors\Generic',
            'somethingHappened',
            'after' => true
        ),
    );

The array elements are (in order): 

* The target class i.e. the class to attach the aspect to.
* The target method i.e. the method to join the aspect methods to.
* The aspect class.
* The aspect class' method i.e. the method to join to the target method.

An aspect may be joined before and/or after its target. This is indicated through the before and after flags demonstrated in the above example.

### Mapping patterns

As of version 1, it's possible to use mapping patterns. Patterns apply only to the target class and method. Currently, only wildcard matching is supported, through the use of asterisks (\*). Below are some examples.


##### Target all methods in every class whose namespace begins with `\Models\ `

        array(
            '\Models\*',
            '*',
            ...

##### Target all methods starting with `get` in every class

        array(
            '\*',
            'get*',
            ...

As with AOP in general, wildcard matching should be used with caution.

## Example

Below are two example classes:

##### \Models\User

    namespace Models;

    class User
    {
        public function getUsername($user_id, &$username)
        {
            echo 'ID: '.$user_id.'<br>';

            $username = 'Demo User';

            return true;
        }
    }

##### \Aspects\Monitors\User

    namespace Aspects\Monitors;

    class User
    {
        public function gettingUsername()
        {
            echo 'Getting a username.<br>';
        }

        public function gotUsername()
        {
            echo 'Got a username.<br>';
        }
    }

Our test initialisation code looks like this:

    $aop = (new \Advocate\AOP())
        ->setMapLocation('app/aop/mapping.php')
        ->setStorageDirectory('app/storage')
        ->addClassResolver(
            (new \Models\ClassResolver\AppClassResolver)
                ->setWorkingDirectory('app')
        )
        ->startUp();

    $user = new \Models\User;
    $user->getUsername(5, $username);
    echo 'Username: '.$username;

Without Advocate, the expected output would be:

> ID: 5

> Username: Demo User

However, with Advocate active, the output is:

> Getting a username.

> ID: 5

> Got a username.

> Username: Demo User