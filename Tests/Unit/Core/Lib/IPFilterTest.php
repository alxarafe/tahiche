<?php
/**
 * This file is part of Tahiche
 * Copyright (C) 2017-2022 Tahiche Team <tahiche@alxarafe.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tahiche\Test\Core\Lib;

use Tahiche\Core\Lib\IPFilter;
use Tahiche\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Description of IPFilterTest
 *
 * @author Tahiche Team <tahiche@alxarafe.com>
 * @covers \Tahiche\Core\Lib\IPFilter
 */
final class IPFilterTest extends TestCase
{
    use LogErrorsTrait;

    const CLEAN_IP = '192.168.0.2';
    const TARGET_IP = '192.168.0.11';

    public function testBanIP()
    {
        $ipFilter = new IPFilter();
        $ipFilter->clear();
        $this->assertFalse($ipFilter->isBanned(self::TARGET_IP), 'target-ip-banned');
        $this->assertFalse($ipFilter->isBanned(self::CLEAN_IP), 'clean-ip-banned');

        for ($attempt = 0; $attempt < IPFilter::MAX_ATTEMPTS; $attempt++) {
            $ipFilter->setAttempt(self::TARGET_IP);
            $this->assertFalse($ipFilter->isBanned(self::TARGET_IP), 'target-ip-banned-' . $attempt);
        }

        $ipFilter->setAttempt(self::TARGET_IP);
        $this->assertTrue($ipFilter->isBanned(self::TARGET_IP), 'target-ip-not-banned');
        $this->assertFalse($ipFilter->isBanned(self::CLEAN_IP), 'clean-ip-banned');

        $ipFilter->clear();
        $this->assertFalse($ipFilter->isBanned(self::TARGET_IP), 'target-ip-banned');
    }

    public function testClearAndSave()
    {
        // band the target ip
        $ipFilter = new IPFilter();
        for ($attempt = 0; $attempt <= IPFilter::MAX_ATTEMPTS; $attempt++) {
            $ipFilter->setAttempt(self::TARGET_IP);
        }
        $this->assertTrue($ipFilter->isBanned(self::TARGET_IP), 'target-ip-not-banned');

        // use another instance
        $ipFilter2 = new IPFilter();
        $this->assertTrue($ipFilter2->isBanned(self::TARGET_IP), 'target-ip-not-banned');
        $ipFilter2->clear();

        // use instance number 3
        $ipFilter3 = new IPFilter();
        $this->assertFalse($ipFilter3->isBanned(self::TARGET_IP), 'target-ip-banned');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
