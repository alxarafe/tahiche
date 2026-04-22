<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Adapter;

use Alxarafe\ResourceController\Contracts\TranslatorContract;
use Tahiche\Infrastructure\Base\Translator;

/**
 * TahicheTranslator — Bridges the ResourceController translation contract.
 */
class TahicheTranslator implements TranslatorContract
{
    public function translate(string $key, array $params = []): string
    {
        return Translator::trans($key, $params);
    }
}
