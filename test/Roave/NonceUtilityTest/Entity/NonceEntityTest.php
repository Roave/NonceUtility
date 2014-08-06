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

namespace Roave\NonceUtilityTest\Entity;

use DateTime;
use PHPUnit_Framework_TestCase;
use Roave\NonceUtility\Entity\NonceEntity;
use Roave\NonceUtility\Stdlib\NonceOwnerInterface;

/**
 * Class NonceEntityTest
 *
 * @coversDefaultClass \Roave\NonceUtility\Entity\NonceEntity
 * @covers ::<!public>
 *
 * @group entity
 */
class NonceEntityTest extends PHPUnit_Framework_TestCase
{
    /**
     * Data provider for the {@see testEntityReturnsSameValueAsSet}
     *
     * @return array
     */
    public function entityPropertyProvider()
    {
        $owner    = $this->getMock(NonceOwnerInterface::class);
        $dateTime = new DateTime();

        return [
            ['nonce',         'roaveIsAwesome'],
            ['namespace',     'roave'],
            ['httpUserAgent', 'httpUserAge'],
            ['ipAddress',     'httpUserAge'],
            ['owner',         $owner],
            ['createdAt',     $dateTime],
            ['expiresAt',     $dateTime, true],
            ['consumedAt',    $dateTime, true],
        ];
    }

    /**
     * @dataProvider entityPropertyProvider
     *
     * @param string $property
     * @param mixed  $value
     * @param bool   $nullable
     */
    public function testEntityReturnsSameValueAsSet($property, $value, $nullable = false)
    {
        $entity = new NonceEntity();

        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);

        if (is_object($value)) {
            $this->assertNull($entity->{$getter}());
        } else {
            $this->assertEmpty($entity->{$getter}());
        }

        if ($nullable) {
            $entity->{$setter}(null);
            $this->assertNull($entity->{$getter}());
        }

        $entity->{$setter}($value);
        $this->assertSame($value, $entity->{$getter}());
    }
}
