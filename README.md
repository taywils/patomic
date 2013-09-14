Patomic - Datomic REST API for PHP
==================================

Requirements
-----------
1. The full Java dev environment [java.com](http://www.java.com) both JDK and JRE
2. Datomic free edition [datomic.com/free](http://downloads.datomic.com/free.html)

Quickstart (Incomplete)
-----------------------

```
$ cd [datomic_directory]
$ ./bin/rest -p 9998 [alias_name] datomic:[datomic_storage_type]://
```

At this point you may browse to [http://localhost:9998/](http://localhost:9998/) and use the offical Datomic REST Service


About
-----
- Patomic provides PHP developers with a RESTful API to communicate with a Datomic database.
- Built upon igorw's wonderful EDN parser for PHP [github.com/igorw/edn](https://github.com/igorw/edn)
- [Guzzle](http://guzzlephp.org/) based web service client
- Uses composer for easy dependency management

So What Is Datomic And Why Should I Care?
-----------------------------------------
- [Datomic Homepage](https://github.com/igorw/edn)
- [Datomic for Five Year Olds](http://www.flyingmachinestudios.com/programming/datomic-for-five-year-olds/)
- [Rich Hickey Introduces Datomic](http://www.youtube.com/watch?v=RKcqYZZ9RDY)
