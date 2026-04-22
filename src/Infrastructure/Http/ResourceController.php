<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Http;

use Alxarafe\ResourceController\AbstractResourceController;
use Alxarafe\ResourceController\Contracts\RepositoryContract;
use Alxarafe\ResourceController\Contracts\TranslatorContract;
use Tahiche\Infrastructure\Adapter\TahicheRepository;
use Tahiche\Infrastructure\Adapter\TahicheTranslator;
use FacturaScripts\Core\Html;

/**
 * ResourceController — Base class for modern CRUD controllers in Tahiche.
 *
 * Extends the ORM-agnostic AbstractResourceController from the resource-controller
 * package, wiring it to FacturaScripts' data layer via FsRepository and FsTranslator.
 *
 * Concrete controllers only need to define:
 * - getModelClassName(): string   — The FS ModelClass to use
 * - getModuleName(): string   — Module name (e.g., 'Trading')
 * - getControllerName(): string — Controller name (e.g., 'Manufacturers')
 *
 * And optionally override:
 * - getListColumns(): array   — Columns for the list view
 * - getEditFields(): array    — Fields for the edit form
 * - getFilters(): array       — Filters for the list view
 */
abstract class ResourceController extends AbstractResourceController
{
    private ?TahicheTranslator $translator = null;
    private ?TahicheRepository $repository = null;

    abstract protected function getModelClassName(): string;

    public function index(): void
    {
        parent::index();
        $this->render();
    }

    protected function render(): void
    {
        // Register our view path if not already registered
        Html::addPath('Tahiche', FS_FOLDER . '/src/Infrastructure/View');

        $descriptor = $this->getViewDescriptor();
        $descriptor['struct'] = $this->structConfig;
        $descriptor['activeTab'] = $this->getActiveTab();
        $descriptor['primaryColumn'] = $this->getModelClassName()::primaryColumn();

        // If we are in list mode, we fetch the actual data to display
        if ($this->mode === 'list') {
            $tabId = $this->getActiveTab();
            $descriptor['listData'] = $this->fetchListData($tabId);
        }

        $descriptor['messages'] = $this->getMessages()->getMessages();

        // Pass the view descriptor to the template
        echo Html::render('@Tahiche/Resource/Main.html.twig', $descriptor);
    }

    protected function getTranslator(): TranslatorContract
    {
        if ($this->translator === null) {
            $this->translator = new TahicheTranslator();
        }
        return $this->translator;
    }

    protected function getRepository(string $tabId = 'default'): RepositoryContract
    {
        if ($this->repository === null) {
            $this->repository = new TahicheRepository($this->getModelClassName());
        }
        return $this->repository;
    }

    public static function getModuleName(): string
    {
        $parts = explode('\\', static::class);
        return $parts[1] ?? '';
    }

    public static function getControllerName(): string
    {
        $parts = explode('\\', static::class);
        $className = end($parts);
        return str_replace('Controller', '', $className);
    }

    public static function url(string $action = 'index', array $params = []): string
    {
        $base = 'index.php?module=' . static::getModuleName() . '&controller=' . static::getControllerName();

        if ($action !== 'index') {
            $base .= '&action=' . $action;
        }

        foreach ($params as $key => $value) {
            $base .= '&' . urlencode($key) . '=' . urlencode((string) $value);
        }

        return $base;
    }
}
