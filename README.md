# SymfonyCouchbase
Bundle for connect Couchbase with Doctrine ORM
The Bundle use the model like Doctrien ORM except for the relations (working in progress with N1QL Join).
For retrive the data for key view are used working in progress to use N1QL and Index (mandatory for quick searchs)
## Installation

Open a command console, enter your project directory and execute the following command to download the latest version of this bundle:

```
composer require fredpalas/couchbase-bundle
```

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...        
        new Apperturedev\CouchbaseBundle\CouchbaseBundle(),
    );
}
```
## Requirements

[JMS Serializer](https://github.com/schmittjoh/JMSSerializerBundle)


## Documentation

WIP
