Patomic - An Object Oriented PHP interface for the Datomic REST API
===================================================================

[![Build Status](https://travis-ci.org/taywils/patomic.svg?branch=master)](https://travis-ci.org/taywils/patomic)

Requirements
-----------
1. The full Java dev environment [java.com](http://www.java.com) both JDK and JRE
2. Datomic free edition [datomic.com/free](http://downloads.datomic.com/free.html)
3. [Composer](http://getcomposer.org/)
4. PHP >= 5.4 (It uses traits)

Quickstart
----------

```
$ cd [directory_where_datomic_is_installed]
$ ./bin/rest -p 9998 example datomic:mem://
```

Now create a new directory somewhere on your hard drive which will hold your new Patomic project files.

```
$ cd ~
$ mkdir [my_patomic_project_name]
$ cd [my_patomic_project_name]
$ touch composer.json
```

Add the following to your composer.json in order to have composer install Patomic for your project.

```
{
  "require": {
    "taywils/patomic": "dev-master",
    "igorw/edn": "1.0.*@dev",
    "nikic/phlexy": "1.0.*@dev"
  }
}
```

Run composer install and watch for any possible errors.
This step may vary depending on how/where you installed composer but for a typical Linux machine running the latest version of Ubuntu.

```
$ sudo php composer.phar install
```

Within the same directory we now want to create a new file; lets call it app.php

```
$ touch app.php
```

Use your favorite editor/IDE and open app.php and add the following.

```
<?php
/* app.php */

require __DIR__ . '/vendor/autoload.php';

use \taywils\Patomic\Patomic;
use \taywils\Patomic\PatomicEntity;
use \taywils\Patomic\PatomicTransaction;
use \taywils\Patomic\PatomicQuery;
use \taywils\Patomic\PatomicException;

function createDb() {
  $patomic = new Patomic("http://localhost", 9998, "mem", "example");
  $patomic->createDatabase("blog");
  $patomic->setDatabase("blog");
}

try {
  createDb();
} catch(PatomicException $pe) {
  echo $pe->getMessage() . PHP_EOL;
}
```

Run it and you should have a new Datomic database using in memory storage

About
-----
- Patomic provides PHP developers with a API to communicate with a Datomic database.
- Built upon igorw's wonderful EDN parser for PHP [github.com/igorw/edn](https://github.com/igorw/edn)
- Uses composer for easy dependency management

So What Is Datomic And Why Should I Care?
-----------------------------------------
- [Datomic Homepage](http://www.datomic.com)
- [Datomic for Five Year Olds](http://www.flyingmachinestudios.com/programming/datomic-for-five-year-olds/)
- [Rich Hickey Introduces Datomic](http://www.youtube.com/watch?v=RKcqYZZ9RDY)
- [Datomic Blog](http://blog.datomic.com/)
