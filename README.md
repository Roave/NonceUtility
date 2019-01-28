# Roave\NonceUtility
[![Build Status](https://travis-ci.org/Roave/NonceUtility.svg)](https://travis-ci.org/Roave/NonceUtility)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Roave/NonceUtility/badges/quality-score.png?s=fb98249a8f4b452b399bc0696f155bed8441cc80)](https://scrutinizer-ci.com/g/Roave/NonceUtility/)
[![Build Status](https://scrutinizer-ci.com/g/Roave/NonceUtility/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Roave/NonceUtility/build-status/master)

A simple module that helps with managing nonces

> Usage of this module assumes that you have already installed and configured
> the DoctrineModule available here http://github.com/doctrine/DoctrineModule

## Installation

Install the module by adding the module to your composer file
```
php composer.phar require roave/roave-nonce-utility:~2.0.0
```

After this you must enable the module by adding `Roave\NonceUtility` to your
`application.config.php` usually found in the `config` directory in the application
root.

Now you need to add an alias for the `Roave\NonceUtility\ObjectManager` to
the object manager of your choice.

This is the standard configuration for most `ORM` users
```php
'service_manager' => [
  'aliases' => [
    'Roave\NonceUtility\ObjectManager' => 'Doctrine\ORM\EntityManager'
  ]
]
```

The last step is to add an entity resolver for our interface to the doctrine
configuration and have that class implement the NonceOwnerInterface

Again for most standard `ORM` users
```php
'doctrine' => [
  'entity_resolver' => array(
    'orm_default' => array(
      'resolvers' => [
        NonceOwnerInterface::class => AbstractUserEntity::class,
      ]
    ]
  ]
]
```

And the nonce owner entity class
```php
abstract class AbstractUser implements NonceOwnerInterface
{
  public function getId()
  {
    // Return your unique identifier.....
  }
}
```

## Usage

To use the module you simply need aquire the nonce service from the service locator

```php
$service = $serviceLocator->get(NonceService::class);
```

The service interface is inlined here
```php
interface NonceServiceInterface
{
    /**
     * Create a new nonce
     *
     * @param NonceOwnerInterface $owner
     * @param string              $namespace
     * @param DateInterval|null   $expiresIn
     * @param integer             $length
     *
     * @return NonceEntity
     */
    public function createNonce(NonceOwnerInterface $owner, $namespace = 'default', DateInterval $expiresIn = null, $length = 10);

    /**
     * Consume a nonce
     *
     * @param NonceOwnerInterface $owner
     * @param string              $nonce
     * @param string              $namespace
     * @param RequestInterface    $request
     *
     * @throws Exception\NonceNotFoundException
     * @throws Exception\NonceAlreadyConsumedException
     * @throws Exception\NonceHasExpiredException
     *
     * @return void
     */
    public function consume(NonceOwnerInterface $owner, $nonce, $namespace = 'default', RequestInterface $request = null);
}
```
