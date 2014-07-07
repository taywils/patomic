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
Once you have met the requirements and downloaded Datomic.

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

Run it and you should have a new Datomic instance using in memory storage running on http://localhost:9998 (If you have an application running on port 9998 feel free to use a different port). For more information on the Datomic REST web interface please read the official documentation found at [http://docs.datomic.com/rest.html](http://docs.datomic.com/rest.html).

Let's create a simple schema to demonstrate how Patomic helps PHP developers take advantage of Datomic. Say you want to create the following schema; (don't forget that Patomic can also import .edn files)

```
{:db/id #db/id[:db.part/db]
 :db/ident :author/firstName
 :db/valueType :db.type/string
 :db/cardinality :db.cardinality/one
 :db/doc "Blog post author's first name"
 :db.install/_attribute :db.part/db}

 {:db/id #db/id[:db.part/db]
 :db/ident :author/lastName
 :db/valueType :db.type/string
 :db/cardinality :db.cardinality/one
 :db/doc "Blog post author's last name"
 :db.install/_attribute :db.part/db}

 {:db/id #db/id[:db.part/db]
 :db/ident :author/favoriteColor
 :db/valueType :db.type/string
 :db/cardinality :db.cardinality/one
 :db/doc "Blog post author's favorite color"
 :db.install/_attribute :db.part/db}
 ```

 Those familiar with EDN will feel right at home but for a PHP developer the following is the Patomic equivalent.

```
/* Add this to your existing app.php */

 function createSchema() {
     $authorFirstName = new PatomicEntity();
     $authorFirstName
        ->ident("author", "firstName")
        ->valueType("string")
        ->cardinality("one")
        ->doc("Blog post author's first name")
        ->install("attribute");

     $authorLastName = new PatomicEntity();
     $authorLastName
        ->ident("author", "lastName")
        ->valueType("string")
        ->cardinality("one")
        ->doc("Blog post author's last name")
        ->install("attribute");

     $authorFavColor = new PatomicEntity();
     $authorFavColor
        ->ident("author", "favoriteColor")
        ->valueType("string")
        ->cardinality("one")
        ->doc("Blog post author's favorite color")
        ->install("attribute");

     $pt = new PatomicTransaction();
     $pt->append($authorFirstName)
        ->append($authorLastName)
        ->append($authorFavColor);

    return $pt;
}
```

Now we'll demonstrate how to add data to our schema, for instance consider the following EDN.

```
[
 {:db/id #db/id [:db.part/user]
  :author/firstName "Sam"
  :author/lastName "Smith"
  :author/favoriteColor "Green"}

 {:db/id #db/id [:db.part/user]
  :author/firstName "Melissa"
  :author/lastName "Grey"
  :author/favoriteColor "Purple"}

 {:db/id #db/id [:db.part/user]
  :author/firstName "Danny"
  :author/lastName "Ward"
  :author/favoriteColor "Orange"}
]
```

Within Patomic we'll represent the transaction to add data as the following.

```
/* Add this to your existing app.php */

function addData() {
    $pt = new PatomicTransaction();

    $pt->addMany(null,
        array("author" => "firstName", "Sam"),
        array("author" => "lastName", "Smith"),
        array("author" => "favoriteColor", "Green")
    );

    $pt->addMany(null,
        array("author" => "firstName", "Melissa"),
        array("author" => "lastName", "Grey"),
        array("author" => "favoriteColor", "Purple")
    );

    $pt->addMany(null,
        array("author" => "firstName", "Danny"),
        array("author" => "lastName", "Ward"),
        array("author" => "favoriteColor", "Orange")
    );

    return $pt;
}
```

To query our data lets first consider the EDN which is what you can use if you are already familiar with Datomic. Don't forget; for advanced users Patomic can use raw EDN queries written as strings.

```
[:find ?entity
 :in $ ?firstName ?lastName
 :where [?entity :author/firstName ?firstName]
        [?entity :author/lastName ?lastName]]
```

Once again those familiar with EDN will be right at home but for PHP devs learning EDN we use PatomicQuery objects.

```
/* Add this to your existing app.php */

function createQuery() {
    $pq = new PatomicQuery();

    $pq->find("firstName", "lastName")
        ->where(array("entity" => "author/firstName", "firstName"))
        ->where(array("entity" => "author/lastName", "lastName"));

    return $pq;
}
```

Putting all of our functions together the last thing we need to do is create a Patomic object that will send our Transactions and run our Queries.

```
<?php

/* Complete app.php */

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

    return $patomic;
}

function createSchema() {
    $authorFirstName = new PatomicEntity();
    $authorFirstName
        ->ident("author", "firstName")
        ->valueType("string")
        ->cardinality("one")
        ->doc("Blog post author's first name")
        ->install("attribute");

    $authorLastName = new PatomicEntity();
    $authorLastName
        ->ident("author", "lastName")
        ->valueType("string")
        ->cardinality("one")
        ->doc("Blog post author's last name")
        ->install("attribute");

    $authorFavColor = new PatomicEntity();
    $authorFavColor
        ->ident("author", "favoriteColor")
        ->valueType("string")
        ->cardinality("one")
        ->doc("Blog post author's favorite color")
        ->install("attribute");

    $pt = new PatomicTransaction();
    $pt->append($authorFirstName)
        ->append($authorLastName)
        ->append($authorFavColor);

    return $pt;
}

function addData() {
    $pt = new PatomicTransaction();

    $pt->addMany(null,
        array("author" => "firstName", "Sam"),
        array("author" => "lastName", "Smith"),
        array("author" => "favoriteColor", "Green")
    );

    $pt->addMany(null,
        array("author" => "firstName", "Melissa"),
        array("author" => "lastName", "Grey"),
        array("author" => "favoriteColor", "Purple")
    );

    $pt->addMany(null,
        array("author" => "firstName", "Danny"),
        array("author" => "lastName", "Ward"),
        array("author" => "favoriteColor", "Orange")
    );

    return $pt;
}

function createQuery() {
    $pq = new PatomicQuery();

    $pq->find("firstName", "lastName")
        ->where(array("entity" => "author/firstName", "firstName"))
        ->where(array("entity" => "author/lastName", "lastName"));

    return $pq;
}

try {
    $patomic = createDb();

    $patomic->commitTransaction( createSchema() );
    $patomic->commitTransaction( addData() );
    $patomic->commitRegularQuery( createQuery() );

    $data = $patomic->getQueryResult();

    print_r($data);
} catch(PatomicException $pe) {
    echo $pe->getMessage() . PHP_EOL;
}
```

Lastly run app.php and you should see the following upon success.

```
INFO: Database "blog" created
INFO: A Patomic object set database to blog
INFO: commitTransaction success
INFO: commitTransaction success
INFO: commitQuery success
Array
(
    [0] => Array
        (
            [firstName] => Danny
            [lastName] => Ward
        )

    [1] => Array
        (
            [firstName] => Sam
            [lastName] => Smith
        )

    [2] => Array
        (
            [firstName] => Melissa
            [lastName] => Grey
        )

)
```

For more information visit the Github page for Patomic [http://taywils.github.io/patomic/](http://taywils.github.io/patomic/)

About/Features
--------------
- Patomic provides PHP developers with a way to communicate with a Datomic database.
- Built upon igorw's wonderful EDN parser for PHP [github.com/igorw/edn](https://github.com/igorw/edn)
- Uses composer for easy dependency management
- Unit tested with PHPUnit
- Can be used with HipHopVM

So What Is Datomic And Why Should I Care?
-----------------------------------------
- [Datomic Homepage](http://www.datomic.com)
- [Datomic for Five Year Olds](http://www.flyingmachinestudios.com/programming/datomic-for-five-year-olds/)
- [Rich Hickey Introduces Datomic](http://www.youtube.com/watch?v=RKcqYZZ9RDY)
- [Datomic Blog](http://blog.datomic.com/)
- [Learn Datalog Today](http://www.learndatalogtoday.org/)
