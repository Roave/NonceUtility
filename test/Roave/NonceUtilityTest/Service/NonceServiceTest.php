<?php
/**
 * @author Antoine Hedgcock
 */

namespace Roave\NonceUtilityTest\Service;

use DateInterval;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit_Framework_MockObject_MockObject;
use Roave\NonceUtility\Entity\NonceEntity;
use Roave\NonceUtility\Repository\NonceRepositoryInterface;
use Roave\NonceUtility\Service\Exception\NonceAlreadyConsumedException;
use Roave\NonceUtility\Service\Exception\NonceHasExpiredException;
use Roave\NonceUtility\Service\Exception\NonceNotFoundException;
use Roave\NonceUtility\Service\NonceService;
use Roave\NonceUtility\Stdlib\NonceOwnerInterface;
use Zend\Http\Request as HttpRequest;

/**
 * Class NonceServiceTest
 *
 * @coversDefaultClass \Roave\NonceUtility\Service\NonceService
 * @covers ::<!public>
 *
 * @group unit
 */
class NonceServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|NonceOwnerInterface
     */
    protected $owner;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|NonceRepositoryInterface
     */
    protected $repository;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var NonceService
     */
    protected $service;

    /**
     * @covers ::__construct
     */
    protected function setUp()
    {
        $this->owner         = $this->getMock(NonceOwnerInterface::class);
        $this->repository    = $this->getMock(NonceRepositoryInterface::class);
        $this->objectManager = $this->getMock(ObjectManager::class);

        $this->service = new NonceService(
            $this->objectManager,
            $this->repository
        );
    }

    /**
     * @covers ::create
     */
    public function testCreateNonceTestUniqueToken()
    {
        $this->repository
            ->expects($this->at(0))
            ->method('has')
            ->will($this->returnValue(true));

        $this->repository
            ->expects($this->at(1))
            ->method('has')
            ->will($this->returnValue(false));

        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(NonceEntity::class));

        $this->objectManager
            ->expects($this->once())
            ->method('flush');

        $nonce = $this->service->create($this->owner);

        $this->assertInstanceOf(NonceEntity::class, $nonce);
    }

    /**
     * @covers ::create
     */
    public function testCreateWithExpirationDate()
    {
        $interval         = new DateInterval('PT10M');
        $expectedDateTime = new DateTime();
        $expectedDateTime->add($interval);

        $this->repository
            ->expects($this->once())
            ->method('has')
            ->will($this->returnValue(false));

        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(NonceEntity::class));

        $nonce = $this->service->create($this->owner, 'default', $interval);

        $this->assertInstanceOf(NonceEntity::class, $nonce);
        $this->assertEquals($expectedDateTime, $nonce->getExpiresAt());
    }

    /**
     * @covers ::create
     */
    public function testCreateWithLength()
    {
        $length = 20;

        $this->repository
            ->expects($this->once())
            ->method('has')
            ->will($this->returnValue(false));

        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(NonceEntity::class));

        $nonce = $this->service->create($this->owner, 'default', null, $length);

        $this->assertEquals($length, strlen($nonce->getNonce()));
    }

    /**
     * @covers ::consume
     */
    public function testConsumeWithMissingNonce()
    {
        $this->setExpectedException(NonceNotFoundException::class);
        $this->service->consume($this->owner, '');
    }

    /**
     * @covers ::consume
     */
    public function testConsumeWithAlreadyConsumedNonce()
    {
        $nonce = new NonceEntity();
        $nonce->setConsumedAt(new DateTime());

        $this->repository
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($nonce));

        $this->setExpectedException(NonceAlreadyConsumedException::class);
        $this->service->consume($this->owner, 'nonce');
    }

    /**
     * @covers ::consume
     */
    public function testConsumeWithExpiredNonce()
    {
        $yesterday = new DateTime();
        $yesterday->modify('-1 days');

        $nonce = new NonceEntity();
        $nonce->setExpiresAt($yesterday);

        $this->repository
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($nonce));

        $this->setExpectedException(NonceHasExpiredException::class);
        $this->service->consume($this->owner, 'nonce');
    }

    /**
     * @covers ::consume
     */
    public function testConsumeWithHttpRequest()
    {
        $nonce = $this->getMock(NonceEntity::class);
        $nonce
            ->expects($this->once())
            ->method('setConsumedAt')
            ->with($this->isInstanceOf(DateTime::class));

        $nonce
            ->expects($this->once())
            ->method('setHttpUserAgent')
            ->with('awesome');

        $this->repository
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($nonce));

        $this->objectManager
            ->expects($this->once())
            ->method('flush');

        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('User-Agent', 'awesome');

        $this->service->consume($this->owner, '', 'default', $request);
    }

    /**
     * @covers ::consume
     */
    public function testConsume()
    {
        $nonce = $this->getMock(NonceEntity::class);
        $nonce
            ->expects($this->once())
            ->method('setConsumedAt')
            ->with($this->isInstanceOf(DateTime::class));

        $nonce
            ->expects($this->never())
            ->method('setHttpUserAgent');

        $this->repository
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($nonce));

        $this->objectManager
            ->expects($this->once())
            ->method('flush');

        $this->service->consume($this->owner, '');
    }

    /**
     * @covers ::createUnassociated
     */
    public function testCreateUnassociatedNonceTestUniqueToken()
    {
        $this->repository
            ->expects($this->at(0))
            ->method('hasUnassociated')
            ->will($this->returnValue(true));

        $this->repository
            ->expects($this->at(1))
            ->method('hasUnassociated')
            ->will($this->returnValue(false));

        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(NonceEntity::class));

        $this->objectManager
            ->expects($this->once())
            ->method('flush');

        $nonce = $this->service->createUnassociated();

        $this->assertInstanceOf(NonceEntity::class, $nonce);
    }

    /**
     * @covers ::create
     */
    public function testCreateUnassociatedWithExpirationDate()
    {
        $interval         = new DateInterval('PT10M');
        $expectedDateTime = new DateTime();
        $expectedDateTime->add($interval);

        $this->repository
            ->expects($this->once())
            ->method('hasUnassociated')
            ->will($this->returnValue(false));

        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(NonceEntity::class));

        $nonce = $this->service->createUnassociated('default', $interval);

        $this->assertInstanceOf(NonceEntity::class, $nonce);
        $this->assertEquals($expectedDateTime, $nonce->getExpiresAt());
    }

    /**
     * @covers ::create
     */
    public function testCreateUnassociatedWithLength()
    {
        $length = 20;

        $this->repository
            ->expects($this->once())
            ->method('hasUnassociated')
            ->will($this->returnValue(false));

        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(NonceEntity::class));

        $nonce = $this->service->createUnassociated('default', null, $length);

        $this->assertEquals($length, strlen($nonce->getNonce()));
    }

    /**
     * @covers ::consumeUnassociated
     */
    public function testConsumeUnassociatedWithMissingNonce()
    {
        $this->setExpectedException(NonceNotFoundException::class);
        $this->service->consumeUnassociated('');
    }

    /**
     * @covers ::consumeUnassociated
     */
    public function testConsumeUnassociatedWithAlreadyConsumedNonce()
    {
        $nonce = new NonceEntity();
        $nonce->setConsumedAt(new DateTime());

        $this->repository
            ->expects($this->once())
            ->method('getUnassociated')
            ->will($this->returnValue($nonce));

        $this->setExpectedException(NonceAlreadyConsumedException::class);
        $this->service->consumeUnassociated('nonce');
    }

    /**
     * @covers ::consumeUnassociated
     */
    public function testConsumeUnassociatedWithExpiredNonce()
    {
        $yesterday = new DateTime();
        $yesterday->modify('-1 days');

        $nonce = new NonceEntity();
        $nonce->setExpiresAt($yesterday);

        $this->repository
            ->expects($this->once())
            ->method('getUnassociated')
            ->will($this->returnValue($nonce));

        $this->setExpectedException(NonceHasExpiredException::class);
        $this->service->consumeUnassociated('nonce');
    }

    /**
     * @covers ::consumeUnassociated
     */
    public function testConsumeUnassociatedWithHttpRequest()
    {
        $nonce = $this->getMock(NonceEntity::class);
        $nonce
            ->expects($this->once())
            ->method('setConsumedAt')
            ->with($this->isInstanceOf(DateTime::class));

        $nonce
            ->expects($this->once())
            ->method('setHttpUserAgent')
            ->with('awesome');

        $this->repository
            ->expects($this->once())
            ->method('getUnassociated')
            ->will($this->returnValue($nonce));

        $this->objectManager
            ->expects($this->once())
            ->method('flush');

        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('User-Agent', 'awesome');

        $this->service->consumeUnassociated('', 'default', $request);
    }

    /**
     * @covers ::consume
     */
    public function testConsumeUnassociated()
    {
        $nonce = $this->getMock(NonceEntity::class);
        $nonce
            ->expects($this->once())
            ->method('setConsumedAt')
            ->with($this->isInstanceOf(DateTime::class));

        $nonce
            ->expects($this->never())
            ->method('setHttpUserAgent');

        $this->repository
            ->expects($this->once())
            ->method('getUnassociated')
            ->will($this->returnValue($nonce));

        $this->objectManager
            ->expects($this->once())
            ->method('flush');

        $this->service->consumeUnassociated('');
    }
}
