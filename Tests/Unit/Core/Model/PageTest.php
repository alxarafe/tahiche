<?php
/**
 * This file is part of Tahiche
 * Copyright (C) 2024-2025 Tahiche Team <tahiche@alxarafe.com>
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

namespace Tahiche\Test\Core\Model;

use Tahiche\Core\Model\Page;
use Tahiche\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class PageTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos
        $page = new Page();
        $page->name = 'test';
        $page->title = 'test';
        $this->assertTrue($page->save(), 'Error saving Page');

        // comprobamos que se ha creado
        $this->assertTrue($page->exists(), 'Page does not exist after saving');

        // eliminamos
        $this->assertTrue($page->delete(), 'Error deleting Page');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
