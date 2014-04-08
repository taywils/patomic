Patomic - A PHP Datomic Thingy
==============================

Requirements
-----------
1. The full Java dev environment [java.com](http://www.java.com) both JDK and JRE
2. Datomic free edition [datomic.com/free](http://downloads.datomic.com/free.html)
3. [Composer](http://getcomposer.org/)
4. PHP >= 5.4 (It uses traits)

Quickstart (Incomplete Don't read this its not ready)
-----------------------------------------------------

```
$ cd [datomic_directory]
$ ./bin/rest -p 9998 [alias_name] datomic:[datomic_storage_type]://
```
Now create a new project to use Patomic

```
$ cd [project_directory]
$ touch composer.json
```

Add Patomic to your composer.json
Run composer install if a new project or composer update for an existing one
Now create a new file lets call it app.php

```
$ touch app.php
```

Use your favorite editor/IDE open app.php and add the following

```
<?php
require __DIR__.'/vendor/autoload.php';

$patomic = new Patomic(9998, "mem", "myAliasName");
$patomic->connect();
$patomic->createDatabase("squid");
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
