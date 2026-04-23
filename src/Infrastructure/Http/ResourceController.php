<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Http;

use Alxarafe\ResourceController\AbstractResourceController;
use Alxarafe\ResourceController\Contracts\RepositoryContract;
use Alxarafe\ResourceController\Contracts\TranslatorContract;
use Alxarafe\ResourceHtml\HtmlRenderer;
use Tahiche\Infrastructure\Adapter\TahicheRepository;
use Tahiche\Infrastructure\Adapter\TahicheTranslator;

/**
 * ResourceController — Base class for modern CRUD controllers in Tahiche.
 *
 * Extends the ORM-agnostic AbstractResourceController from the resource-controller
 * package, wiring it to FacturaScripts' data layer via TahicheRepository and
 * TahicheTranslator. Rendering uses pure PHP templates via resource-html's
 * HtmlRenderer — no Twig or Blade dependency.
 *
 * Concrete controllers only need to define:
 * - getModelClassName(): string   — The FS ModelClass to use
 * - getModuleName(): string       — Module name (e.g., 'Trading')
 * - getControllerName(): string   — Controller name (e.g., 'Manufacturers')
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
    private ?HtmlRenderer $renderer = null;

    abstract protected function getModelClassName(): string;

    public function index(): void
    {
        parent::index();
        $this->render();
    }

    /**
     * Returns the HtmlRenderer instance, creating it on first use.
     *
     * Uses a two-path strategy:
     *  1. Project-local path  → allows per-project template overrides
     *  2. Package default path → templates shipped with resource-html
     */
    protected function getRenderer(): HtmlRenderer
    {
        if ($this->renderer === null) {
            // 1. Local project path (overrides)
            $projectPath = (defined('FS_FOLDER') ? FS_FOLDER : getcwd())
                           . '/src/Infrastructure/View';

            // 2. Package path (defaults shipped with resource-html)
            $packagePath = dirname(
                (new \ReflectionClass(HtmlRenderer::class))->getFileName()
            ) . '/View';

            $this->renderer = new HtmlRenderer([$projectPath, $packagePath]);
        }
        return $this->renderer;
    }

    /**
     * Renders the view using pure PHP templates (no Twig).
     */
    protected function render(): void
    {
        $descriptor = $this->getViewDescriptor();
        $descriptor['struct'] = $this->structConfig;
        $descriptor['activeTab'] = $this->getActiveTab();
        $descriptor['primaryColumn'] = $this->getModelClassName()::primaryColumn();

        // Fetch list data if in list mode
        if ($this->mode === 'list') {
            $tabId = $this->getActiveTab();
            $descriptor['listData'] = $this->fetchListData($tabId);
        }

        $descriptor['messages'] = $this->getMessages()->getMessages();

        // Provide a translator callable for templates: $t('key')
        $translator = $this->getTranslator();
        $descriptor['t'] = static function (string $key, array $params = []) use ($translator): string {
            return $translator->translate($key, $params);
        };

        // CSRF token (if available from the legacy system)
        $descriptor['csrfToken'] = $_COOKIE['multireqtoken'] ?? '';

        // Select template based on mode
        $template = ($this->mode === 'list') ? 'Resource/list' : 'Resource/edit';

        // Render inner content
        $renderer = $this->getRenderer();
        $bodyContent = $renderer->render($template, $descriptor);

        // Wrap in layout
        $title = $this->getTranslator()->translate(static::getControllerName());
        echo $renderer->render('Resource/layout', [
            'title' => $title,
            'bodyContent' => $bodyContent,
            'lang' => defined('FS_LANG') ? substr(FS_LANG, 0, 2) : 'es',
        ]);
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
