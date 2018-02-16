<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Apperturedev\CouchbaseBundle\Classes;

use JMS\Serializer\Serializer;
use Doctrine\ORM\EntityManager;
use CouchbaseBucket;
use CouchbaseViewQuery;

/**
 * CouchbaseManager ORM entity manager.
 *
 * @author adrian
 */
class CouchbaseManager extends Functions
{
    private $entity;
    private $em;
    private $doctrine;
    private $serializer;

    /**
     * [__construct description]
     * @param [Object]          $entity     [description]
     * @param CouchbaseBucket $em         [description]
     * @param EntityManager   $doctrine   [description]
     * @param Serializer      $serializer [description]
     */
    public function __construct(
    $entity, CouchbaseBucket $em, EntityManager $doctrine, Serializer $serializer
    )
    {
        $this->em         = $em;
        $this->entity     = new $entity();
        $this->doctrine   = $doctrine;
        $this->serializer = $serializer;
        $this->setSerializer($serializer);
    }

    /**
     * Return the document by id
     * format object, array or crud value.
     *
     * @param int $id
     * @param string $format
     *
     * @return Object
     */
    public function getById($id, $format = 'object')
    {
        $id    = intval($id);
        $table = $this->doctrine->getClassMetadata(get_class($this->entity))->getTableName();
        $res   = $this->classToArray($this->em->get($table.'_'.$id)->value);

        return $this->serializer->fromArray($res, get_class($this->entity));
    }

    /**
     * return all register about the entity in the expected format, object, array or crud value.
     *
     * @param string $format the expected, object, array or value
     *
     * @return type
     */
    public function getAll($format = 'object')
    {
        $table = $this->doctrine->getClassMetadata(get_class($this->entity))->getTableName();
        $query = CouchbaseViewQuery::from($table, 'id');

        return $this->execute($query, $format);
    }

    /**

     * Get view data.
     *
     *
     * @param string $field the view name or Object Propierty
     *
     * @return \_CouchbaseDefaultViewQuery
     */
    public function get($field)
    {
        $table = $this->doctrine->getClassMetadata(get_class($this->entity))->getTableName();
        $query = CouchbaseViewQuery::from($table, $field);
        $query->stale(1);

        return $query;
    }

    /**
     * execute the view query.
     *
     * @param \CouchbaseViewQuery $query
     * @param string $format object, array or value
     *
     * @return Object
     */
    public function execute(CouchbaseViewQuery $query, $format = 'object')
    {
        $res = $this->em->query($query, null, true);

        if (is_object($res)) {
            $res = $this->classToArray($res);
        }

        if ($res['total_rows'] == 0 and count($res['rows']) == 0) {
            return null;
        }

        foreach ($res['rows'] as $value) {
            if ('value' != $format) {
                $this->entity = $this->serializer->fromArray($value['value'], get_class($this->entity));
            }
            switch ($format) {
                case 'object':
                    $entidad[] = $this->entity;
                    break;
                case 'array':
                    $entidad[] = $this->serializer->toArray($this->entity);
                    break;
                case 'value':
                    $entidad[] = $value['value'];
                    break;
            }
        }

        return (count($entidad) == 1) ? $entidad[0] : $entidad;
    }

    /**
     * Execute N1QL query.
     *
     * @param type $query
     *
     * @return type
     */
    public function query($query)
    {
        $sql = CouchbaseN1qlQuery::fromString($query);

        return $result = $this->em->query($sql);
    }

    /**
     * Truncate All documents of a Entity.
     *
     * @return type
     */
    public function truncateDocumemts()
    {
        $data = $this->getAll('value');
        $name = $this->doctrine->getClassMetadata(get_class($this->entity))->getTableName();
        if (null != $data) {
            foreach ($data as $value) {
                $this->em->remove($name.'_'.$value['id']);
            }
            $this->em->remove($name.'_id');

            return array('Success' => true);
        } else {
            return array('Success' => true, 'msg' => 'Not Registers');
        }
    }

    /**
     * Del a document by id.
     *
     * @param type $id
     *
     * @return type
     */
    public function delDocumemt($id)
    {
        $name = $this->doctrine->getClassMetadata(get_class($this->entity))->getTableName();
        try {
            $this->em->remove($name.'_'.$id);
        } catch (\Exception $e) {
            return array('Success' => true, 'msg' => 'Not Register');
        }

        return array('Success' => true);
    }
}