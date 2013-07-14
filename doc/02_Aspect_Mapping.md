# Aspect mapping

As demonstrated in **Installation and Usage**, example syntax for the mapping file is:

    <?php

    return array(
        array('\Models\User', 'getUsername', '\Aspects\Monitors\User', 'gotUsername'),
        array('\Models\User', 'getUsername', '\Aspects\Monitors\Generic', 'somethingHappened'),
    );

The four array elements are (in order): 

* The target class i.e. the class to attach the aspect to.
* The target method i.e. the method to join the aspect methods to.
* The aspect class.
* The aspect class' method i.e. the method to join to the target method.

As demonstrated in the introduction, this mapping will join `\Aspects\Monitors\User`'s `gotUsername` and `\Aspects\Monitors\Generic`'s `somethingHappened` methods to `\Models\User`'s `getUsername` method.

### Mapping patterns

It's planned for future developments to allow patterns for matching. However, the current version only supports exact name matching.

## Example

Below are three example classes:

##### \Models\User

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

##### \Aspects\Monitors\User

    namespace Aspects\Monitors;

    class User
    {
        public function gotUsername()
        {
            echo 'Got a username.<br>';
        }
    }

##### \Aspects\Monitors\Generic

    namespace Aspects\Monitors;

    class Generic
    {
        public function somethingHappened()
        {
            echo 'Something happened<br>';
        }
    }

Our test initialisation code looks like this:

    $aop = new \Advocate\AOP('app');
    $aop->init();

    $user = new \Models\User;
    $user->getUsername(5, $user);
    echo 'Username: '.$user;

Without Advocate, the expected output would be:

> Getting username for ID: 5

> Username: Demo User

However, with Advocate active, the output is:

> Getting username for ID: 5

> Got a username.

> Something happened

> Username: Demo User