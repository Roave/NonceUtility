<?php
/**
 * @author Antoine Hedgcock
 */

namespace Roave\NonceUtilityTest\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Roave\NonceUtility\Entity\NonceEntity;
use Roave\NonceUtility\Repository\NonceRepository;
use Roave\NonceUtility\Stdlib\NonceOwnerInterface;

/**
 * Class NonceRepositoryTest
 *
 * @coversDefaultClass \Roave\NonceUtility\Repository\NonceRepository
 * @covers ::<!public>
 *
 * @group repository
 */
class NonceRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var NonceRepository
     */
    protected $repository;

    /**
     * @var ObjectRepository|PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRepository;

    /**
     * @covers ::__construct
     */
    protected function setUp()
    {
        $this->objectRepository = $this->getMock(ObjectRepository::class);
        $this->repository = new NonceRepository($this->objectRepository);
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $ownerId   = 1337;
        $nonce     = 'roaveIsAwesome';
        $namespace = 'partyRoom';

        $nonceObject = new NonceEntity();

        $owner = $this->getMock(NonceOwnerInterface::class);
        $owner
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($ownerId));

        $this->objectRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['owner' => $ownerId, 'nonce' => $nonce, 'namespace' => $namespace])
            ->will($this->returnValue($nonceObject));

        $result = $this->repository->get($owner, $nonce, $namespace);
        $this->assertSame($nonceObject, $result);
    }

    /**
     * @covers ::has
     */
    public function testHasWithFalseResponse()
    {
        $owner   = $this->getMock(NonceOwnerInterface::class);
        $builder = $this->getMockBuilder(NonceRepository::class);
        $builder
            ->setMethods(['get'])
            ->disableOriginalConstructor();

        /** @var NonceRepository|PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $builder->getMock();
        $repository
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(null));

        $this->assertFalse($repository->has($owner, 'testCase'));
    }

    /**
     * @covers ::has
     */
    public function testHasWithTrueResponse()
    {
        $owner   = $this->getMock(NonceOwnerInterface::class);
        $builder = $this->getMockBuilder(NonceRepository::class);
        $builder
            ->setMethods(['get'])
            ->disableOriginalConstructor();

        /** @var NonceRepository|PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $builder->getMock();
        $repository
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(new NonceEntity()));

        $this->assertTrue($repository->has($owner, 'testCase'));
    }

    /**
     * @covers ::getUnassociated
     */
    public function testGetUnassociated()
    {
        $nonce     = 'roaveIsAwesome';
        $namespace = 'partyRoom';

        $nonceObject = new NonceEntity();

        $this->objectRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['owner' => null, 'nonce' => $nonce, 'namespace' => $namespace])
            ->will($this->returnValue($nonceObject));

        $result = $this->repository->getUnassociated($nonce, $namespace);
        $this->assertSame($nonceObject, $result);
    }

    /**
     * @covers ::hasUnassociated
     */
    public function testHasUnassociatedWithFalseResponse()
    {
        $builder = $this->getMockBuilder(NonceRepository::class);
        $builder
            ->setMethods(['getUnassociated'])
            ->disableOriginalConstructor();

        /** @var NonceRepository|PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $builder->getMock();
        $repository
            ->expects($this->once())
            ->method('getUnassociated')
            ->will($this->returnValue(null));

        $this->assertFalse($repository->hasUnassociated('testCase'));
    }

    /**
     * @covers ::hasUnassociated
     */
    public function testHasUnassociatedWithTrueResponse()
    {
        $builder = $this->getMockBuilder(NonceRepository::class);
        $builder
            ->setMethods(['getUnassociated'])
            ->disableOriginalConstructor();

        /** @var NonceRepository|PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $builder->getMock();
        $repository
            ->expects($this->once())
            ->method('getUnassociated')
            ->will($this->returnValue(new NonceEntity()));

        $this->assertTrue($repository->hasUnassociated('testCase'));
    }
}
