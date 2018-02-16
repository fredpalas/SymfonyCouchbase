<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Apperturedev\CouchbaseBundle\Classes;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use JMS\Serializer\SerializationContext;

/**
 * Usefull Functions
 *
 * @author adrian
 */
class Functions {
    
    private $name;
    private $_prototype;
    private $serializer;
    private $context;
   
    /**
     * Seting outside serializer
     * @param type $serializer
     * @return boolean
     */
    public function setSerializer($serializer){
        $this->serializer = $serializer;
        $context = new SerializationContext();
        $this->context=$context->setSerializeNull(true);
        return true;
    }
    


     /**
     * Transform object to Json.
     *
     * Will return one object to json
     *
     *     $this->toJson($class);
     *
     * @param class          $class  A object Class
     *
     * @return Json String
     */
    public function toJSon($class){        
    $encoders = array( new JsonEncoder());
    $normalizers = array(new ObjectNormalizer());
    $serializer = new Serializer($normalizers, $encoders);
    $jsonContent = $serializer->serialize($class, 'json');
    return $jsonContent;
    }
    /**
     * object to array, witout JMS serializer
     * @param type $class
     * @return type
     */
    public function onArray($class){
        
        $reflection = new \ReflectionObject($class);
        foreach ($reflection->getProperties() as $property){
                        // Override visibility
            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }
            $array[$property->name] = $property->getValue($class);
        }
        return $array;
    }
    
    /**
     * Symfony standart normalizer object to array
     * @param type $class
     * @return type
     */
    public function objecttoArray($class){
        $normalizers = new PropertyNormalizer();
        return $normalizers->normalize($class);
    }


    /**
     * Improved symfony normalizer
     * @param type $class
     * @return type
     */
    public function toArray($class){
        $json = $this->onArray($class);
        return $json;
    }
    
    /**
     * Convert an array to Entity object transforming to json and json to object
     * @param type $array
     * @param type $class
     */
    public function toObject($array,&$class){
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $class = $serializer->deserialize(json_encode($array), get_class($class), 'json');
    }
    /**
     * Convert a Json Sring in a Entity Object
     * @param type $json
     * @param type $class
     */
    public function JsontoObjectold($json,&$class){
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $class = $serializer->deserialize($json, get_class($class), 'json');
    }
    
    /**
     * JMS Serializer Json string to Entity Object
     * @param type $json
     * @param type $class
     * @return boolean
     */
    public function JsontoObject($json,&$class){
        $class=$this->serializer->deserialize($json,get_class($class),'json',  $this->context);
        return true;
    }
    
    /**
     * Set the id of private id from Array
     * @param type $array
     * @param type $class
     */
    public function getObject($array,&$class){
        $this->name = get_class($class);
        $entity = $this->newInstance();
        $this->toObject($array, $entity);
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $array['id']);
        $class = $entity;
    }
    
    
    private function newInstance()
    {
        if ($this->_prototype === null) {
            $this->_prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }
        return clone $this->_prototype;
    }
    /**
     * Json String to Array
     * @param type $json
     * @return type
     */
    public function JsonToArray($json){
        $json = json_decode($json);
        $result = array();
        foreach ($json as $key=>$value){
            $result[$key]=$value;
        }
        return $result;
    }
    /**
     * Json Object to Array
     * @param type $json
     * @return type
     */
    public function JsonObjectToArray($json){
        $result = array();
        foreach ($json as $key=>$value){
            $result[$key]=$value;
        }
        return $result;
    }
    public function classToArray($data)
    {
        return json_decode(json_encode($data),true);
    }
}
