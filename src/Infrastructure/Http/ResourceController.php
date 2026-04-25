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
                (new \ReflectionClass(DefaultRenderer::class))->getFileName(), 3
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

        // Force conversion to array to resolve all Field objects into plain arrays
        // This ensures the translateStructure recursive function works correctly.
        $descriptor = json_decode(json_encode($descriptor), true);

        // Apply modifiers to the whole descriptor
        $descriptor = \Tahiche\Infrastructure\Base\Registry::apply('descriptor_' . static::class, $descriptor);

        $descriptor['struct'] = $descriptor['config'] ?? $this->structConfig;

        // Apply modifiers to the structure (fields, columns, tabs) specifically
        $descriptor['struct'] = \Tahiche\Infrastructure\Base\Registry::apply('struct_' . static::class, $descriptor['struct']);

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

        $pageData = $this->getPageData();
        $descriptor['pageData'] = $pageData;
        $descriptor['title'] = $translator->translate($pageData['title'] ?? 'edit_record');

        // Gather all translations needed by the JS engine
        $descriptor['translations'] = [
            'edit_record' => $descriptor['title'],
            'save' => $translator->translate('save'),
            'back' => $translator->translate('back'),
            'loading_data' => $translator->translate('loading_data'),
            'actions' => $translator->translate('actions'),
            'no_records_found' => $translator->translate('no_records_found'),
            'confirm_delete' => $translator->translate('confirm_delete'),
            'delete' => $translator->translate('delete'),
            'reload' => $translator->translate('reload'),
        ];

        // Recursively translate all labels and titles in the structure
        // This is necessary because the pre-compiled JS engine uses labels directly
        $this->translateStructure($descriptor['struct'], $translator);

        // Adjust descriptor mapping for JavaScript Engine which expects 'config' instead of 'struct'
        $descriptor['config'] = $descriptor['struct'];
        unset($descriptor['struct']);

        // Select template based on mode
        $template = ($this->mode === 'list') ? 'layout/list' : 'layout/edit';

        // Load all HTML templates into the descriptor so the JS engine can render declaratively
        $descriptor['templates'] = [];
        $packagePath = dirname((new \ReflectionClass(DefaultRenderer::class))->getFileName(), 3) . '/templates';
        if (is_dir($packagePath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($packagePath));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'html') {
                    // Build the correct template key expected by the JS engine
                    $relativePath = str_replace($packagePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);

                    if (str_starts_with($relativePath, 'component/form/fields/')) {
                        // e.g. "component/form/fields/edit/text.html" -> "text_edit"
                        $parts = explode('/', substr($relativePath, 0, -5));
                        $mode = $parts[count($parts) - 2];
                        $type = end($parts);
                        $key = $type . '_' . $mode;
                    } else {
                        // e.g. "layout/list.html" -> "layout_list"
                        $key = str_replace('/', '_', substr($relativePath, 0, -5));
                    }

                    $htmlTemplate = file_get_contents($file->getPathname());

                    // Customization: Use our external, extensible template
                    if ($key === 'layout_edit') {
                        $icon = $pageData['icon'] ?? 'fas fa-edit';
                        $title = $descriptor['title'];

                        // Load external template
                        $overridePath = (defined('FS_FOLDER') ? FS_FOLDER : getcwd())
                                       . '/src/Infrastructure/View/layout/edit.html';
                        if (is_file($overridePath)) {
                            $htmlTemplate = file_get_contents($overridePath);
                        }

                        // Replace page-level placeholders
                        $htmlTemplate = str_replace('[page:title]', htmlspecialchars($title), $htmlTemplate);
                        $htmlTemplate = str_replace('[page:icon]', htmlspecialchars($icon), $htmlTemplate);

                        // Generate dynamic buttons HTML from head_buttons
                        $extraButtonsHtml = '';
                        $headButtons = $descriptor['config']['edit']['head_buttons'] ?? [];
                        foreach ($headButtons as $btn) {
                            // Skip standard buttons (save, delete, etc.)
                            $btnName = $btn['name'] ?? '';
                            if (in_array($btnName, ['save', 'delete', 'back', ''])) {
                                continue;
                            }
                            $btnLabel = $btn['label'] ?? $btnName;
                            $btnIcon = $btn['icon'] ?? '';
                            $btnType = $btn['type'] ?? 'secondary';
                            $btnAction = $btn['action'] ?? '';

                            $iconHtml = $btnIcon ? '<i class="' . htmlspecialchars($btnIcon) . ' me-1"></i> ' : '';
                            $extraButtonsHtml .= '<button type="button" class="btn btn-sm btn-'
                                . htmlspecialchars($btnType) . ' ms-2 shadow-sm" data-action="'
                                . htmlspecialchars($btnAction) . '">'
                                . $iconHtml . htmlspecialchars($btnLabel) . '</button>';
                        }

                        // Inject buttons into the template slots
                        $htmlTemplate = str_replace(
                            '<span id="alxarafe-extra-buttons"></span>',
                            '<span id="alxarafe-extra-buttons">' . $extraButtonsHtml . '</span>',
                            $htmlTemplate
                        );
                    }

                    // Pre-translate tags [trans:key]
                    $htmlTemplate = preg_replace_callback('/\[trans:([^\]]+)\]/', function ($matches) use ($translator) {
                        return $translator->translate($matches[1]);
                    }, $htmlTemplate);

                    $descriptor['templates'][$key] = $htmlTemplate;
                }
            }
        }

        // Render inner content only — the Kernel wraps it with the legacy layout
        $renderer = $this->getRenderer();
        $innerHtml = $renderer->render($template, $descriptor);

        $uniqueId = 'alxarafe-resource-' . uniqid();
        $html = "<div id='{$uniqueId}'>\n{$innerHtml}\n</div>";

        // Inject the configuration and initialization script for the frontend engine
        $jsonConfig = json_encode($descriptor, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $script = "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var container = document.getElementById('{$uniqueId}').querySelector('.alxarafe-resource-list, .alxarafe-resource-edit');
                if (!container) {
                    console.error('[AlxarafeResource] Container not found for {$uniqueId}.');
                    return;
                }
                
                var ResourceClass = null;
                if (typeof window.AlxarafeResource === 'function') {
                    ResourceClass = window.AlxarafeResource; // default export or no wrapper
                } else if (window.AlxarafeResource && typeof window.AlxarafeResource.AlxarafeResource === 'function') {
                    ResourceClass = window.AlxarafeResource.AlxarafeResource; // named export in UMD
                } else if (typeof AlxarafeResource === 'function') {
                    ResourceClass = AlxarafeResource;
                }
                
                if (ResourceClass) {
                    new ResourceClass(container, {$jsonConfig});
                } else {
                    console.error('[AlxarafeResource] JS Engine class not found in window.');
                }
            });
        </script>";

        echo $html . $script;
    }

    /**
     * Override processResults to inject an 'id' field to satisfy the pre-compiled JS frontend engine
     * which looks for 'id' or 'code' rather than dynamic primary columns.
     */
    protected function processResults(array $items, array $columns): array
    {
        $results = parent::processResults($items, $columns);
        $primaryKey = $this->getModelClassName()::primaryColumn();

        foreach ($results as &$row) {
            if (!isset($row['id']) && isset($row[$primaryKey])) {
                $row['id'] = $row[$primaryKey];
            }
        }

        return $results;
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

        return $this->getRenderer()->render('layout/list', $descriptor);
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

    private function translateStructure(array &$struct, $translator): void
    {
        foreach ($struct as $key => &$value) {
            if (is_array($value)) {
                if (isset($value['label']) && is_string($value['label'])) {
                    $value['label'] = $translator->translate($value['label']);
                }
                if (isset($value['title']) && is_string($value['title'])) {
                    $value['title'] = $translator->translate($value['title']);
                }
                $this->translateStructure($value, $translator);
            }
        }
    }
}
