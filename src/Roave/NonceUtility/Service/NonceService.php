<?php
/**
 * Copyright (c) 2014 Roave, LLC.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Antoine Hedgecock
 *
 * @copyright 2014 Roave, LLC
 * @license http://www.opensource.org/licenses/bsd-license.php  BSD License
 */
namespace Roave\NonceUtility\Service;

use DateInterval;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Roave\NonceUtility\Entity\NonceEntity;
use Roave\NonceUtility\Repository\NonceRepositoryInterface;
use Roave\NonceUtility\Stdlib\NonceOwnerInterface;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Http\Request as HttpRequest;
use Zend\Math\Rand;
use Zend\Stdlib\RequestInterface;

/**
 * Class NonceService
 */
class NonceService implements NonceServiceInterface
{
    /**
     * @var NonceRepositoryInterface
     */
    private $repository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @param ObjectManager            $objectManager
     * @param NonceRepositoryInterface $repository
     */
    public function __construct(ObjectManager $objectManager, NonceRepositoryInterface $repository)
    {
        $this->repository    = $repository;
        $this->objectManager = $objectManager;
    }

    /**
     * {@Inheritdoc}
     */
    public function createNonce(
        NonceOwnerInterface $owner,
        $namespace = 'default',
        DateInterval $expiresIn = null,
        $length = 10
    ) {
        do {
            $nonce = strtr(Rand::getString($length), '+/', '-_');
        } while ($this->repository->has($owner, $nonce, $namespace));

        $entity = new NonceEntity();
        $entity->setOwner($owner);
        $entity->setNonce($nonce);
        $entity->setNamespace($namespace);
        $entity->setCreatedAt(new DateTime());

        if ($expiresIn !== null) {
            $expiresAt = new DateTime();
            $expiresAt->add($expiresIn);

            $entity->setExpiresAt($expiresAt);
        }

        $this->objectManager->persist($entity);
        $this->objectManager->flush();

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function consume(
        NonceOwnerInterface $owner,
        $nonce,
        $namespace = 'default',
        RequestInterface $request = null
    ) {
        $nonce = $this->repository->get($owner, $nonce, $namespace);

        if (! $nonce) {
            throw new Exception\NonceNotFoundException;
        }

        if ($nonce->getConsumedAt() !== null) {
            throw new Exception\NonceAlreadyConsumedException;
        }

        $now = new DateTime();
        if ($nonce->getExpiresAt() !== null && $nonce->getExpiresAt() < $now) {
            throw new Exception\NonceHasExpiredException;
        }

        $nonce->setConsumedAt($now);

        // Add additional information if a request object was passed
        if ($request instanceof HttpRequest) {

            $httpUserAgentHeader = $request->getHeader('User-Agent');
            if ($httpUserAgentHeader) {
                $nonce->setHttpUserAgent($httpUserAgentHeader->getFieldValue());
            }

            $nonce->setIpAddress((new RemoteAddress())->getIpAddress());
        }

        $this->objectManager->flush();
    }
}
