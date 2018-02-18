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

```yml
# app/config/config.yml
couchbase:
    url: <couchbase.url>
    buckets:
        default: #dafult bucket
            bucket_name: <bucket name>         
```
## Requirements

[JMS Serializer](https://github.com/schmittjoh/JMSSerializerBundle)


## Documentation

```php

//in action throw container

public function indexAction()
{
    /** @var Apperturedev\CouchbaseBundle\Classes\CouchbaseORM $couchbase editor Helper */
    $couchbase = $this->get('couchbase');
    
    $entity = New Entity();
    // do anything
    
    // save
    $couchbase->save($entity);
    
    $entity->getId();  // Will set the id Automatic
    
    $repository = $couchbase->getRepository('Bundle:Entity');
    // get data
    $entity1 = $repository->getById(1);
    
    /** For Run Couchbase View you need to run bin/console couchbase:generate:view Bundle:Entity */
    /** Fixing a bug for moving old version class */
    // country example           
    $query = $repository->get('country');
    $query->key('Spain')->order(\CouchbaseViewQuery::ORDER_ASCENDING)->limit(6);

    // Will return a array if more than 1 or the object if is 1
    $country = $repository->execute($query);
}

```

