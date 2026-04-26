<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Tahiche\Infrastructure\Bridge\LegacyBridgeTrait;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Internal\ClassResolver;
use FacturaScripts\Core\Model\CodeModel;

class TestDinamicController extends ResourceController
{
    use LegacyBridgeTrait;

    protected function setup(): void
    {
        $this->structConfig = [
            'id' => 'test-dinamic',
            'title' => 'Test de Compatibilidad Dinamic',
            'icon' => 'fas fa-vial',
            'components' => [
                'list' => [
                    'type' => 'custom',
                    'content' => $this->runTests(),
                ]
            ]
        ];
    }

    public function runTests(): string
    {
        $html = "<h3>Resultados de Test de Compatibilidad Dinamic</h3>";
        $html .= "<ul class='list-group'>";

        // Test 1: src -> Legacy (Instanciando un modelo que asume Dinamic)
        $html .= $this->testSrcToLegacy();

        // Test 2: src -> Core (CodeModel que fue movido al Core)
        $html .= $this->testSrcToCore();

        // Test 3: Legacy -> src (ClassResolver y TypeHints relajados)
        $html .= $this->testLegacyToSrc();

        $html .= "</ul>";
        return $html;
    }

    private function testSrcToLegacy(): string
    {
        try {
            // Simulamos que el sistema moderno intenta leer algo que un plugin legacy cree que está en Dinamic.
            // Usamos el resolver para obtener la clase real.
            $dinamicClass = "\\FacturaScripts\\Dinamic\\Model\\Impuesto";
            $realClass = ClassResolver::getRealClass($dinamicClass) ?? $dinamicClass;

            if (class_exists($realClass)) {
                $model = new $realClass();
                return "<li class='list-group-item list-group-item-success'><b>[OK] src -> Legacy:</b> Se resolvió $dinamicClass hacia $realClass exitosamente.</li>";
            }
            return "<li class='list-group-item list-group-item-danger'><b>[FAIL] src -> Legacy:</b> No se encontró la clase $dinamicClass.</li>";
        } catch (\Throwable $e) {
            return "<li class='list-group-item list-group-item-danger'><b>[FAIL] src -> Legacy:</b> Excepción: " . $e->getMessage() . "</li>";
        }
    }

    private function testSrcToCore(): string
    {
        try {
            // Instanciar CodeModel (movido de Dinamic a Core)
            $codeModel = new CodeModel();
            return "<li class='list-group-item list-group-item-success'><b>[OK] src -> Core:</b> CodeModel instanciado correctamente desde el Core (" . get_class($codeModel) . ").</li>";
        } catch (\Throwable $e) {
            return "<li class='list-group-item list-group-item-danger'><b>[FAIL] src -> Core:</b> Excepción: " . $e->getMessage() . "</li>";
        }
    }

    private function testLegacyToSrc(): string
    {
        try {
            // Simulamos que un plugin (Admin) devuelve un modelo que el Core legacy (con dependencias relajadas) debe aceptar.
            // Vamos a invocar a la clase Empresas del Core que usa get() y retorna un ModelClass.
            $empresa = \FacturaScripts\Plugins\BusinessBase\DataSrc\Empresas::get(1);
            if ($empresa) {
                return "<li class='list-group-item list-group-item-success'><b>[OK] Legacy -> src/Core:</b> Empresas::get() del Core devolvió " . get_class($empresa) . " sin Fatal TypeError.</li>";
            }
            return "<li class='list-group-item list-group-item-warning'><b>[WARN] Legacy -> src/Core:</b> No se encontró la empresa 1.</li>";
        } catch (\Throwable $e) {
            return "<li class='list-group-item list-group-item-danger'><b>[FAIL] Legacy -> src/Core:</b> Excepción (probablemente TypeHint strict): " . $e->getMessage() . "</li>";
        }
    }

    public function getEditFields(): array
    {
        return [];
    }

    public function getModelClassName(): string
    {
        return '';
    }
}
