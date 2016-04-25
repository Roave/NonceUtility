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
namespace Roave\NonceUtility\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Roave\NonceUtility\Stdlib\NonceOwnerInterface;

class NonceRepository implements NonceRepositoryInterface
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    private $objectRepository;

    /**
     * @param ObjectRepository $objectRepository
     */
    public function __construct(ObjectRepository $objectRepository)
    {
        $this->objectRepository = $objectRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function get(NonceOwnerInterface $owner = null, $nonce, $namespace = 'default')
    {
        return $this->objectRepository->findOneBy([
            'owner'     => $owner ? $owner->getId() : null,
            'nonce'     => $nonce,
            'namespace' => $namespace
        ]);
    }

    /**
     * {@Inheritdoc}
     */
    public function has(NonceOwnerInterface $owner = null, $nonce, $namespace = 'default')
    {
        return $this->get($owner, $nonce, $namespace) !== null;
    }

    /**
     * {@Inheritdoc}
     */
    public function garbageCollect()
    {
        if (! $this->objectRepository instanceof EntityRepository) {
            throw new \DomainException('Not yet implemented');
        }
        
        /** @var QueryBuilder $builder */
        $builder = $this->objectRepository->createQueryBuilder('nonce');
        $builder
            ->delete()
            ->andWhere('nonce.expiresAt IS NOT NULL')
            ->andWhere('nonce.expiresAt < CURRENT_TIMESTAMP()')
            ->andWhere('nonce.consumedAt IS NULL');

        $query = $builder->getQuery();
        $query->execute();
    }
}
