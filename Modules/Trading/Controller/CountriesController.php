<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Fields;
use Modules\Trading\Model\Country;

class CountriesController extends ResourceController
{
    public static function getModuleName(): string
    {
        return 'Trading';
    }

    public static function getControllerName(): string
    {
        return 'Countries';
    }

    protected function getModelClassName(): string
    {
        return Country::class;
    }

    public function getPageData(): array
    {
        return [
            'name'       => 'ListPais',
            'title'      => 'countries',
            'icon'       => 'fa-solid fa-globe-americas',
            'menu'       => 'admin',
            'submenu'    => null,
            'showonmenu' => true,
            'ordernum'   => 100,
        ];
    }

    protected function getListColumns(): array
    {
        return [
            new Fields\Text('codpais', 'code'),
            new Fields\Text('nombre', 'name'),
            new Fields\Text('codiso', 'iso_code'),
        ];
    }

    protected function getEditFields(): array
    {
        return [
            new Fields\Text('codpais', 'code', ['required' => true]),
            new Fields\Text('nombre', 'name', ['required' => true]),
            new Fields\Text('codiso', 'iso_code'),
        ];
    }

    protected function buildConfiguration(): void
    {
        parent::buildConfiguration();

        if ($this->mode === 'edit' && $this->recordId && $this->recordId !== 'new') {
            $provincesController = new ProvincesController();
            $provincesHtml = $provincesController->renderListFragment(['codpais' => $this->recordId]);

            $this->addEditSection('provinces', 'provinces');
            $this->addEditField('provinces', new Fields\StaticText('', ['content' => $provincesHtml, 'col' => 'col-12']));
        }
    }
}
