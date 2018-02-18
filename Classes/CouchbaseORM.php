<?php

namespace Apperturedev\CouchbaseBundle\Classes;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use CouchbaseCluster;

//use Symfony\Component\Serializer\Serializer;

/**
 * CouchbaseORM MANAGER.
 *
 * @author adrian
 */
class CouchbaseORM extends Functions
{
    private $em;
    private $doctrine;
    private $_entity;
    private $serializer;
    private $format;
    private $context;
    private $buckets;

    /**
     * @param type          $Couchbase  url couchbase
     * @param type          $bucket     the bucket name
     * @param EntityManager $doctrine
     * @param type          $serializer $JMS Serializer
     * @param type          $format     Saved format
     */
    public function __construct(
    CouchbaseCluster $em, EntityManager $doctrine, Serializer $serializer, array $buckets = null
    )
    {
        $this->buckets    = $buckets;
        $bucket           = isset($buckets['default']['bucket_name']) ? $buckets['default']['bucket_name'] : null;
        $bucketPassword   = isset($buckets['default']['bucket_password']) ? $buckets['default']['bucket_password'] : '';
        $this->em         = $em->openBucket($bucket, $bucketPassword);
        //$this->em->enableN1ql($Couchbase);
        $this->doctrine   = $doctrine;
        $this->serializer = $serializer;
        $this->setSerializer($serializer);
    }

    /**
     * Get the Couchbase manager.
     *
     * @return \CouchbaseBucket
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * Get JMS Serializer.
     *
     * @return SerializerInterface
     */
    public function getSer()
    {
        return $this->serializer;
    }

    /**
     * Save the entity Object on Couchbase
     * If id is null create a new one and add automatically to the Entity Object.
     *
     * @param type $class
     *
     * @return type
     *
     * @throws \Exception
     */
    public function save($class)
    {
        $table = $this->doctrine->getClassMetadata(get_class($class))->getTableName();
        if (null === $class->getId()) {
            $this->setObjectId($class, $this->setNextId($class));
        }
        $name            = $table.'_'.$class->getId();
        $data            = $this->serializer->toArray($class, $this->getContext());
        $data['doctype'] = $table;
        $debug           = $this->em->upsert($name, $data);
        if (null == $debug->error) {
            return $debug;
        } else {
            throw new \Exception('Something went wrong!');
        }
    }

    /**
     * set the id.
     *
     * @param type $class
     * @param type $id
     */
    private function setObjectId($class, $id)
    {
        $reflection = new \ReflectionObject($class);
        $property   = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($class, $id);
    }

    /**
     * Get CouchbaseORM manager.
     *
     * @param type $entityname
     *
     * @return \Apperturedev\CouchbaseBundle\Classes\CouchbaseManager
     */
    public function getRepository($entityname)
    {
        $entity        = $this->doctrine->getClassMetadata($entityname)->getName();
        $this->_entity = new CouchbaseManager($entity, $this->em, $this->doctrine, $this->serializer);
        return $this->_entity;
    }

    /**
     * Get the last id of a Entity Object.
     *
     * @param type $class
     *
     * @return type
     */
    public function getLastId($class)
    {
        $table   = $this->doctrine->getClassMetadata(get_class($class))->getTableName();
        $noexist = false;
        try {
            $value = $this->em->get($table.'_id');
            $id    = ($value->value->id > 1) ? ($value->value->id - 1) : 1;
        } catch (\Exception $e) {
            //echo 'Excepción capturada: ',  $e->getMessage(), "\n";
            $noexist = true;
        }
        if ($noexist) {
            $id = $this->setId($class);
        }

        return $id;
    }

    /**
     * Set next ID.
     *
     * @param type $class
     *
     * @return type
     *
     * @throws \Exception
     */
    private function setNextId($class)
    {
        $table   = $this->doctrine->getClassMetadata(get_class($class))->getTableName();
        $noexist = false;
        try {
            $getDoc = $this->em->get($table.'_id');
            $id     = $getDoc->value->id;
        } catch (\Exception $e) {
            $noexist = true;
        }
        if ($noexist) {
            return $this->setId($class);
        } else {
            $debug = $this->em->replace($table.'_id', array('id' => $id + 1));
            if (null == $debug->error) {
                return $id;
            } else {
                throw new \Exception('Something went wrong!');
            }
        }
    }

    /**
     * Set id if don't exist.
     *
     * @param type $class
     * @param type $save
     *
     * @return int
     *
     * @throws \Exception
     */
    private function setId($class, $save = false)
    {
        $table   = $this->doctrine->getClassMetadata(get_class($class))->getTableName();
        $noexist = false;
        try {
            $id = $this->em->get($table.'_id');
        } catch (\Exception $e) {
            //echo 'Excepción capturada: ',  $e->getMessage(), "\n";
            $noexist = true;
        }
        if ($noexist) {
            $id    = ($save) ? 2 : 1;
            $datas = array('id' => $id);
            $debug = $this->em->insert($table.'_id', $datas);
            if (!is_null($debug->error)) {
                throw new \Exception('Something went wrong!');
            }
        }
        return $id;
    }   

    private function getContext()
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);

        return $context;
    }
}