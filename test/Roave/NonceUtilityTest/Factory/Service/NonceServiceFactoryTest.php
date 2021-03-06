<?php
/**
 * @author Antoine Hedgcock
 */

namespace Roave\NonceUtilityTest\Factory\Service;

use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit_Framework_TestCase;
use Roave\NonceUtility\Factory\Service\NonceServiceFactory;
use Roave\NonceUtility\Repository\NonceRepository;
use Roave\NonceUtility\Repository\NonceRepositoryInterface;
use Roave\NonceUtility\Service\NonceService;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class NonceServiceFactoryTest
 *
 * @coversDefaultClass \Roave\NonceUtility\Factory\Service\NonceServiceFactory
 * @covers ::<!public>
 *
 * @group factory
 */
class NonceServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::createService
     */
    public function testCreateService()
    {
        $sl = $this->getMock(ServiceLocatorInterface::class);
        $sl
            ->expects($this->at(0))
            ->method('get')
            ->with('Roave\NonceUtility\ObjectManager')
            ->will($this->returnValue($this->getMock(ObjectManager::class)));

        $sl
            ->expects($this->at(1))
            ->method('get')
            ->with(NonceRepository::class)
            ->will($this->returnValue($this->getMock(NonceRepositoryInterface::class)));

        $factory = new NonceServiceFactory();
        $service = $factory->createService($sl);

        $this->assertInstanceOf(NonceService::class, $service);
    }
}
