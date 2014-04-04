<?php
/**
 * @author Antoine Hedgcock
 */

namespace Roave\NonceUtilityTest\Service;

use DateInterval;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Roave\NonceUtility\Entity\NonceEntity;
use Roave\NonceUtility\Repository\NonceRepositoryInterface;
use Roave\NonceUtility\Service\Exception\NonceAlreadyConsumedException;
use Roave\NonceUtility\Service\Exception\NonceHasExpiredException;
use Roave\NonceUtility\Service\Exception\NonceNotFoundException;
use Roave\NonceUtility\Service\NonceService;
use Roave\NonceUtility\Stdlib\NonceOwnerInterface;
use Zend\Http\Request as HttpRequest;

class NonceServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|NonceOwnerInterface
     */
    protected $owner;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|NonceRepositoryInterface
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var NonceService
     */
    protected $service;

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

        $this->service->createNonce($this->owner);
    }

    public function testCreateWithExpirationDate()
    {
        $interval = new DateInterval('PT10M');

        $this->repository
            ->expects($this->once())
            ->method('has')
            ->will($this->returnValue(false));

        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (NonceEntity $nonce) use ($interval) {

                $expiresAt = new DateTime();
                $expiresAt->add($interval);

                return $nonce->getExpiresAt() == $expiresAt;
            }));

        $this->service->createNonce($this->owner, 'default', $interval);
    }

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
            ->with($this->callback(function (NonceEntity $nonce) use ($length) {

                return strlen($nonce->getNonce()) == $length;
            }));

        $this->service->createNonce($this->owner, 'default', null, $length);
    }

    public function testConsumeWithMissingNonce()
    {
        $this->setExpectedException(NonceNotFoundException::class);
        $this->service->consume($this->owner, '');
    }

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
}
