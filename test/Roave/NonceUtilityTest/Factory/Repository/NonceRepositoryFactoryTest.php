<?php
/**
 * @author Antoine Hedgcock
 */

namespace Roave\NonceUtilityTest\Factory\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit_Framework_TestCase;
use Roave\NonceUtility\Entity\NonceEntity;
use Roave\NonceUtility\Factory\Repository\NonceRepositoryFactory;
use Roave\NonceUtility\Repository\NonceRepositoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class NonceRepositoryFactoryTest
 *
 * @coversDefaultClass \Roave\NonceUtility\Factory\Repository\NonceRepositoryFactory
 * @covers ::<!public>
 *
 * @group factory
 */
class NonceRepositoryFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::createService
     */
    public function testCreateService()
    {
        $factory = new NonceRepositoryFactory();

        $repository    = $this->getMock(ObjectRepository::class);
        $objectManager = $this->getMock(ObjectManager::class);
        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(NonceEntity::class)
            ->will($this->returnValue($repository));

        $sl = $this->getMock(ServiceLocatorInterface::class);
        $sl
            ->expects($this->once())
            ->method('get')
            ->with('Roave\NonceUtility\ObjectManager')
            ->will($this->returnValue($objectManager));

        $repository = $factory->createService($sl);

        $this->assertInstanceOf(NonceRepositoryInterface::class, $repository);
    }
}
