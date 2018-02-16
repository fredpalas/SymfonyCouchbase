<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Apperturedev\CouchbaseBundle\Tests\Classes;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use CouchbaseCluster;
use Apperturedev\CouchbaseBundle\Classes\CouchbaseORM;
use Apperturedev\CouchbaseBundle\Classes\CouchbaseManager;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use CouchbaseBucket;
use JMS\Serializer\Serializer;
use PHPUnit_Framework_MockObject_MockObject;
use Apperturedev\TestBundle\Entity\Test;
use Doctrine\ORM\Mapping\ClassMetadata;
use stdClass;
use Exception;

class CouchbaseORMTest extends TestCase
{
    /**
     *
     * @var CouchbaseCluster|PHPUnit_Framework_MockObject_MockObject
     */
    private $couchbaseCluster;

    /**
     *
     * @var CouchbaseORM
     */
    private $couchbaseORM;

    /**
     *
     * @var EntityManager|PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrine;

    /**
     *
     * @var Serializer|PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     *
     * @var CouchbaseBucket|PHPUnit_Framework_MockObject_MockObject
     */
    private $couchbaseBucket;

    const ANY_BUCKET           = 'anyBucket';
    const ANY_BUCKET_PASSSWORD = 'anyPassword';

    private $anyName      = 'anyName';
    private $anyUsername  = 'anyUsername';
    private $anyNumber    = 123456;
    private $anyTableName = 'test';

    public function setUp()
    {
        $this->couchbaseCluster = $this->getMockBuilder(CouchbaseCluster::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine         = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer       = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configArray            = $this->setConfigArray();
        $this->couchbaseBucket  = $this->getCouchbaseBucket();
        $this->couchbaseCluster->expects($this->atLeastOnce())
            ->method('openBucket')
            ->with($configArray['default']['bucket_name'], $configArray['default']['bucket_password'])
            ->willReturn($this->couchbaseBucket);
        $this->couchbaseORM     = new CouchbaseORM(
            $this->couchbaseCluster, $this->doctrine, $this->serializer, $configArray
        );
    }

    public function testSetSerializer()
    {
        $a = $this->couchbaseORM->setSerializer($this->serializer);
        $this->assertEquals($a, true);
    }

    public function testGetEm()
    {
        $a = $this->couchbaseORM->getEm();
        $this->assertEquals($a, $this->getCouchbaseBucket());
    }

    public function testGetSer()
    {
        $a = $this->couchbaseORM->getSer();
        $this->assertEquals($a, $this->serializer);
    }

    public function testSaveIdOne()
    {
        $id        = 1;
        $test      = $this->getTestEntity();
        $testArray = $this->getTestEntityArray($id);

        $this->mockTableName();
        $this->mockGetTableId($id);
        $this->mockReplaceTestTableId($id);
        $this->mockSerializerToArray($test, $testArray);

        $testArray['doctype'] = 'test';
        $doc                  = 'test_'.$id;

        $this->mockUpsert($doc, $testArray, false);
        $this->couchbaseORM->save($test);
        $this->assertEquals(1, $test->getId());
    }

    public function testSaveIdError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something went wrong!');
        $id        = 1;
        $test      = $this->getTestEntity();
        $testArray = $this->getTestEntityArray($id);

        $this->mockTableName();
        $this->mockGetTableId($id);
        $this->mockReplaceTestTableId($id);
        $this->mockSerializerToArray($test, $testArray);

        $testArray['doctype'] = 'test';
        $doc                  = 'test_'.$id;

        $this->mockUpsert($doc, $testArray, true);
        $this->couchbaseORM->save($test);
    }

    public function testSaveErrorTableReplace()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something went wrong!');
        $id        = 1;
        $test      = $this->getTestEntity();
        $testArray = $this->getTestEntityArray($id);

        $this->mockTableName();
        $this->mockGetTableId($id);
        $this->mockReplaceTestTableId($id, true);
        $this->mockSerializerToArray($test, $testArray);

        $testArray['doctype'] = 'test';
        $doc                  = 'test_'.$id;

        $this->mockUpsert($doc, $testArray);
        $this->couchbaseORM->save($test);
    }

    public function testSaveErrorId()
    {
        $id        = 1;
        $test      = $this->getTestEntity();
        $testArray = $this->getTestEntityArray($id);

        $this->mockTableName();
        $exception = $this->throwException(new Exception());
        $this->mockGetTableId($exception);
        $this->mockInsertTableId($id, null, $this->once());
        $this->mockReplaceTestTableId($id);
        $this->mockSerializerToArray($test, $testArray);

        $testArray['doctype'] = 'test';
        $doc                  = 'test_'.$id;

        $this->mockUpsert($doc, $testArray);
        $this->couchbaseORM->save($test);

        $testArray['doctype'] = 'test';
        $doc                  = 'test_'.$id;

        $this->assertEquals(1, $test->getId());
    }

    public function testSaveErrorIdErrorInsert()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something went wrong!');
        $id        = 1;
        $test      = $this->getTestEntity();
        $this->mockTableName();
        $exception = $this->throwException(new Exception());
        $this->mockGetTableId($exception);
        $this->mockInsertTableId($id, 'error', $this->once());

        $this->couchbaseORM->save($test);
    }

    public function testGetLastId()
    {
        $id   = 2;
        $test = $this->getTestEntity();
        $this->mockTableName();
        $this->mockGetTableId($id);
        $this->mockReplaceTestTableId($id);

        $idReturn = $this->couchbaseORM->getLastId($test);

        $this->assertEquals(1, $idReturn);
    }

    public function testSaveErrorIdGetLastId()
    {
        $id        = 1;
        $test      = $this->getTestEntity();
        $this->mockTableName();
        $exception = $this->throwException(new Exception());
        $this->mockGetTableId($exception);
        $this->mockInsertTableId($id, null, $this->once());
        $this->mockReplaceTestTableId($id);

        $idReturn = $this->couchbaseORM->getLastId($test);

        $this->assertEquals(1, $idReturn);
    }

    public function testGetRepository()
    {
        $this->mockClassName();
        $entity = $this->couchbaseORM->getReposity('TestBundle:Test');

        $couchbaseEntity = new CouchbaseManager(Test::class, $this->couchbaseBucket, $this->doctrine, $this->serializer);

        $this->assertEquals($entity, $couchbaseEntity);
    }

    /**
     *
     * @return Test
     */
    private function getTestEntity()
    {
        $test = new Test();
        $test->setName($this->anyName);
        $test->setUsername($this->anyUsername);
        $test->setNumber($this->anyNumber);
        return $test;
    }

    private function getTestEntityArray($id)
    {
        $test = $this->getTestEntity();
        $data = [
            'id' => $id,
            'name' => $test->getName(),
            'username' => $test->getUsername(),
            'number' => $test->getNumber()
        ];
        return $data;
    }

    private function mockSerializerToArray($class, $arrayClass)
    {
        $this->serializer->expects($this->any())
            ->method('toArray')
            ->with($class, $this->getContext())
            ->willReturn($arrayClass);
    }

    /**
     *
     * @return array
     */
    private function setConfigArray()
    {
        $bucket = [
            'default' => [
                'bucket_name' => self::ANY_BUCKET,
                'bucket_password' => self::ANY_BUCKET_PASSSWORD
            ]
        ];
        return $bucket;
    }

    /**
     *
     * @return CouchbaseBucket|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCouchbaseBucket()
    {
        /* @var $a CouchbaseBucket|\PHPUnit_Framework_MockObject_MockObject */
        $a = $this->getMockBuilder(CouchbaseBucket::class)->disableOriginalConstructor()->getMock();
        return $a;
    }

    private function mockTableName()
    {
        $metaData = $this->getClassMetadata();
        $this->doctrine->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->with(Test::class)
            ->willReturn($metaData);
        $metaData->expects($this->atLeastOnce())
            ->method('getTableName')
            ->willReturn($this->anyTableName);
    }

    private function mockClassName()
    {

        $metaData = $this->getClassMetadata();
        $this->doctrine->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->with('TestBundle:Test')
            ->willReturn($metaData);
        $metaData->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn(Test::class);
    }

    /**
     *
     * @return ClassMetadata|PHPUnit_Framework_MockObject_MockObject
     */
    private function getClassMetadata()
    {
        $a = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        return $a;
    }

    /**
     *
     * @param type $return
     */
    private function mockGetTableId($return)
    {
        $parameter = $this->anyTableName.'_id';
        $a         = $this->couchbaseBucket->expects($this->any())
            ->method('get')
            ->with($parameter);
        if ($return instanceof \PHPUnit_Framework_MockObject_Stub_Exception) {
            $a->will($return);
        } else {
            $a->willReturn($this->dataForGet($return));
        }
    }

    private function dataForGet($return)
    {
        $data            = new \stdClass();
        $data->value     = new stdClass();
        $data->value->id = $return;
        return $data;
    }

    /**
     *
     * @param int $id
     * @param $return
     */
    private function mockInsertTableId($id, $return, $many)
    {
        $data        = new \stdClass();
        $data->error = $return;
        $parameter   = $this->anyTableName.'_id';
        $datas       = array('id' => $id);
        $this->couchbaseBucket->expects($many)
            ->method('insert')
            ->with($parameter, $datas)
            ->willReturn($data);
    }

    /**
     *
     * @param int $id
     * @param bool $error
     */
    private function mockReplaceTestTableId($id, bool $error = false)
    {
        $data        = new \stdClass();
        $data->error = null;
        $parameter   = $this->anyTableName.'_id';
        $datas       = array('id' => $id + 1);
        $this->mockReplace($parameter, $datas, $error);
    }

    /**
     *
     * @param type $table
     * @param type $data
     * @param bool $error
     */
    private function mockReplace($table, $data, bool $error = false)
    {
        $dataReturn        = new stdClass();
        $dataReturn->error = ($error) ? 'error' : null;
        $this->couchbaseBucket->expects($this->any())
            ->method('replace')
            ->with($table, $data)
            ->willReturn($dataReturn);
    }

    /**
     *
     * @param string $doc
     * @param array $data
     * @param bool $error
     */
    private function mockUpsert(string $doc, array $data, bool $error = false)
    {
        $dataReturn        = new stdClass();
        $dataReturn->error = ($error) ? 'error' : null;
        $this->couchbaseBucket->expects($this->any())
            ->method('upsert')
            ->with($doc, $data)
            ->willReturn($dataReturn);
    }

    /**
     *
     * @return SerializationContext
     */
    private function getContext()
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);

        return $context;
    }
}