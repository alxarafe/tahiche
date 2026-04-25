<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\BankAccount;
use Alxarafe\ResourceController\Component\Fields;
use Alxarafe\ResourceController\Component\Container\Panel;
use FacturaScripts\Core\Model\Empresa;

class BankAccountsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return BankAccount::class;
    }

    public function getPageData(): array
    {
        return [
            'name'       => 'BankAccounts',
            'title'      => 'bank-account',
            'icon'       => 'fa-solid fa-piggy-bank',
            'menu'       => 'accounting',
            'submenu'    => null,
            'showonmenu' => true,
            'ordernum'   => 100,
        ];
    }

    protected function getListColumns(): array
    {
        return [
            'codcuenta',
            'descripcion',
            'iban',
            ['field' => 'activa', 'type' => 'boolean']
        ];
    }

    protected function getEditFields(): array
    {
        $empresas = [];
        $empModel = new Empresa();
        foreach ($empModel->all() as $emp) {
            $empresas[$emp->idempresa] = $emp->nombrecorto;
        }

        return [
            new Fields\Integer('codcuenta', 'code', ['readonly' => true, 'col' => 2]),
            new Fields\Text('descripcion', 'description', ['required' => true, 'maxlength' => 100, 'col' => 10]),

            new Fields\Text('swift', 'swift', ['col' => 6, 'maxlength' => 11]),
            new Fields\Text('iban', 'iban', ['col' => 6, 'maxlength' => 34]),

            new Fields\Select('idempresa', 'company', $empresas, ['required' => true, 'col' => 6]),
            new Fields\Text('sufijosepa', 'sepa-suffix', ['col' => 6, 'maxlength' => 3]),

            // Simulating the legacy subaccount lookup with an action/icon (even if JS needs update)
            (new Fields\Text('codsubcuenta', 'subaccount', ['col' => 6]))
                ->addAction('fas fa-search', 'window.openSubaccountSearch()'),
            (new Fields\Text('codsubcuentagasto', 'expense-subaccount', ['col' => 6]))
                ->addAction('fas fa-search', 'window.openSubaccountSearch()'),

            new Fields\Boolean('activa', 'active', ['col' => 12])
        ];
    }

    public function index(): void
    {
        parent::index();

        // Display related subaccounts only in edit mode and not during AJAX requests
        $id = $_GET['id'] ?? null;
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || ($_GET['format'] ?? '') === 'json';

        if ($this->mode === 'edit' && $id && !$isAjax) {
            $record = $this->getRepository()->find($id);
            $this->renderRelatedSubaccounts($record['codsubcuenta'] ?? null, $record['codsubcuentagasto'] ?? null);
        }
    }

    private function renderRelatedSubaccounts(?string $codsub, ?string $codgas): void
    {
        if (!$codsub && !$codgas) {
            return;
        }

        // Direct query to Subaccounts repository to simplify things
        $repo = (new \Modules\Accounting\Controller\SubaccountsController())->getRepository();
        $subaccounts = [];

        if ($codsub) {
            $subaccounts = array_merge($subaccounts, $repo->all(['codsubcuenta' => $codsub]));
        }
        if ($codgas) {
            $subaccounts = array_merge($subaccounts, $repo->all(['codsubcuenta' => $codgas]));
        }

        if (empty($subaccounts)) {
            return;
        }

        echo "<div class='container-fluid mt-4'>";
        echo "<h3 class='h5 mb-3 text-primary'><i class='fa-solid fa-book me-2'></i>" . i18n()->trans('subaccounts') . "</h3>";
        echo "<div class='card shadow-sm'><div class='table-responsive'><table class='table table-hover mb-0'>";
        echo "<thead class='bg-light'><tr><th>" . i18n()->trans('code') . "</th><th>" . i18n()->trans('description') . "</th><th class='text-end'>" . i18n()->trans('actions') . "</th></tr></thead><tbody>";

        foreach ($subaccounts as $sub) {
            $editUrl = "index.php?module=Accounting&controller=Subaccounts&id=" . ($sub['codsubcuenta'] ?? '');
            echo "<tr>";
            echo "<td><code class='text-primary'>" . ($sub['codsubcuenta'] ?? '') . "</code></td>";
            echo "<td>" . ($sub['descripcion'] ?? '') . "</td>";
            echo "<td class='text-end'><a href='{$editUrl}' class='btn btn-sm btn-outline-primary'><i class='fas fa-pen'></i></a></td>";
            echo "</tr>";
        }

        echo "</tbody></table></div></div></div>";
    }
}
