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
        // Mapeo de claves comunes del ecosistema Resource que pueden faltar en el legacy
        $mapping = [
            'save_changes' => 'save',
        ];

        $key = $mapping[$key] ?? $key;

        return Translator::trans($key, $params);
    }
}
