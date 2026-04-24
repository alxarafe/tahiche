<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Http;

use Alxarafe\ResourceController\AbstractResourceController;
use Alxarafe\ResourceController\Contracts\RepositoryContract;
use Alxarafe\ResourceController\Contracts\TranslatorContract;
use Alxarafe\ResourceController\Render\DefaultRenderer;
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
 * The Kernel wraps the output of these controllers in the legacy MenuTemplate
 * layout, so they share the same menu, CSS/JS and visual chrome as the rest
 * of the ERP.
 *
 * Concrete controllers only need to define:
 * - getModelClassName(): string   — The FS ModelClass to use
 *
 * And optionally override:
 * - getListColumns(): array   — Columns for the list view
 * - getEditFields(): array    — Fields for the edit form
 * - getFilters(): array       — Filters for the list view
 * - getPageData(): array      — Menu position (menu, submenu, icon, etc.)
 */
abstract class ResourceController extends AbstractResourceController
{
    private ?TahicheTranslator $translator = null;
    private ?TahicheRepository $repository = null;
    private ?DefaultRenderer $renderer = null;

    abstract protected function getModelClassName(): string;

    /**
     * Main entry point. Called by the Kernel.
     * Executes the ResourceTrait lifecycle and renders the inner content.
     * The Kernel captures this output and wraps it with the legacy layout.
     */
    public function index(): void
    {
        parent::index();
        $this->render();
    }

    /**
     * Returns the DefaultRenderer instance, creating it on first use.
     *
     * Uses a two-path strategy:
     *  1. Project-local path  → allows per-project template overrides
     *  2. Package default path → templates shipped with resource-controller
     */
    protected function getRenderer(): DefaultRenderer
    {
        if ($this->renderer === null) {
            // 1. Local project path (overrides)
            $projectPath = (defined('FS_FOLDER') ? FS_FOLDER : getcwd())
                           . '/src/Infrastructure/View';

            // 2. Package path (defaults shipped with resource-controller)
            $packagePath = dirname(
                (new \ReflectionClass(DefaultRenderer::class))->getFileName(), 2
            ) . '/templates';

            $this->renderer = new DefaultRenderer([$projectPath, $packagePath]);
        }
        return $this->renderer;
    }

    /**
     * Renders only the inner content of the view (no HTML/head/layout).
     * The Kernel is responsible for wrapping this in the legacy MenuTemplate.
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

        // Render inner content only — the Kernel wraps it with the legacy layout
        $renderer = $this->getRenderer();
        echo $renderer->render($template, $descriptor);
    }

    /**
     * Renders a sub-list HTML fragment to be embedded in other views.
     */
    public function renderListFragment(array $conditions = []): string
    {
        $this->mode = 'list';
        $this->beforeConfig();
        $this->buildConfiguration();

        // Apply embedded conditions
        $tabId = $this->getActiveTab() ?: 'general';
        foreach ($conditions as $field => $val) {
            $this->structConfig['list']['tabs'][$tabId]['conditions'][$field] = $val;
        }

        $descriptor = $this->getViewDescriptor();
        $descriptor['struct'] = $this->structConfig;
        $descriptor['activeTab'] = $tabId;
        $descriptor['primaryColumn'] = $this->getModelClassName()::primaryColumn();
        $descriptor['listData'] = $this->fetchListData($tabId);
        $descriptor['messages'] = [];
        
        // Disable main buttons and headers for fragments
        $descriptor['struct']['list']['head_buttons'] = [];
        $descriptor['isFragment'] = true;

        $translator = $this->getTranslator();
        $descriptor['t'] = static function (string $key, array $params = []) use ($translator): string {
            return $translator->translate($key, $params);
        };
        $descriptor['csrfToken'] = $_COOKIE['multireqtoken'] ?? '';

        return $this->getRenderer()->render('Resource/list', $descriptor);
    }

    /**
     * Returns page metadata for legacy menu integration.
     * Override in concrete controllers to position them in the ERP menu
     * at the same location as the legacy controller they replace.
     *
     * Keys: name, title, menu, submenu, icon, showonmenu, ordernum
     */
    public function getPageData(): array
    {
        return [
            'name'       => static::getControllerName(),
            'title'      => static::getControllerName(),
            'icon'       => 'fa-solid fa-circle',
            'menu'       => 'new',
            'submenu'    => null,
            'showonmenu' => true,
            'ordernum'   => 100,
        ];
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
