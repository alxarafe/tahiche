<?php

$pluginName = 'Crm';
$pluginDir = __DIR__ . "/Plugins/{$pluginName}";

$models = [
    'Cliente', 'Proveedor', 'Contacto', 'GrupoClientes', 'Agente', 'Sector'
];

$controllers = [
    'EditCliente', 'ListCliente', 
    'EditProveedor', 'ListProveedor', 
    'EditContacto', 'ListContacto', 
    'EditGrupoClientes', 'ListGrupoClientes', 
    'EditAgente', 'ListAgente', 
    'EditSector', 'ListSector'
];

$tables = [
    'clientes', 'proveedores', 'contactos', 'gruposclientes', 'agentes', 'sectores'
];

// Create dirs
@mkdir($pluginDir, 0777, true);
@mkdir("$pluginDir/Model", 0777, true);
@mkdir("$pluginDir/Controller", 0777, true);
@mkdir("$pluginDir/Table", 0777, true);
@mkdir("$pluginDir/XMLView", 0777, true);

// 1. Process Models
foreach ($models as $model) {
    $coreFile = __DIR__ . "/Core/Model/{$model}.php";
    $pluginFile = "$pluginDir/Model/{$model}.php";
    
    if (file_exists($coreFile)) {
        // Copy to plugin
        $content = file_get_contents($coreFile);
        
        // Update namespace
        $content = str_replace(
            "namespace FacturaScripts\\Core\\Model;", 
            "namespace FacturaScripts\\Plugins\\{$pluginName}\\Model;", 
            $content
        );
        
        // Update internal usages of the models being extracted
        foreach ($models as $subModel) {
            $content = str_replace(
                "FacturaScripts\\Core\\Model\\{$subModel}", 
                "FacturaScripts\\Plugins\\{$pluginName}\\Model\\{$subModel}", 
                $content
            );
        }
        
        file_put_contents($pluginFile, $content);
        
        // Create hollow bridge in core
        $bridgeContent = "<?php\n\nnamespace FacturaScripts\\Core\\Model;\n\n";
        $bridgeContent .= "/**\n * @deprecated Moved to Plugins\\{$pluginName}\\Model\n */\n";
        $bridgeContent .= "class {$model} extends \\FacturaScripts\\Plugins\\{$pluginName}\\Model\\{$model}\n{\n}\n";
        
        file_put_contents($coreFile, $bridgeContent);
        echo "Extracted Model: $model\n";
    }
}

// 2. Process Controllers
foreach ($controllers as $ctrl) {
    $coreFile = __DIR__ . "/Core/Controller/{$ctrl}.php";
    $pluginFile = "$pluginDir/Controller/{$ctrl}.php";
    
    if (file_exists($coreFile)) {
        $content = file_get_contents($coreFile);
        
        $content = str_replace(
            "namespace FacturaScripts\\Core\\Controller;", 
            "namespace FacturaScripts\\Plugins\\{$pluginName}\\Controller;", 
            $content
        );
        
        // Update internal usages of the models being extracted
        foreach ($models as $subModel) {
            $content = str_replace(
                "FacturaScripts\\Core\\Model\\{$subModel}", 
                "FacturaScripts\\Plugins\\{$pluginName}\\Model\\{$subModel}", 
                $content
            );
        }
        
        // Update internal usages of the controllers being extracted
        foreach ($controllers as $subCtrl) {
            $content = str_replace(
                "FacturaScripts\\Core\\Controller\\{$subCtrl}", 
                "FacturaScripts\\Plugins\\{$pluginName}\\Controller\\{$subCtrl}", 
                $content
            );
        }
        
        file_put_contents($pluginFile, $content);
        
        $bridgeContent = "<?php\n\nnamespace FacturaScripts\\Core\\Controller;\n\n";
        $bridgeContent .= "/**\n * @deprecated Moved to Plugins\\{$pluginName}\\Controller\n */\n";
        $bridgeContent .= "class {$ctrl} extends \\FacturaScripts\\Plugins\\{$pluginName}\\Controller\\{$ctrl}\n{\n}\n";
        
        file_put_contents($coreFile, $bridgeContent);
        echo "Extracted Controller: $ctrl\n";
    }
}

// 3. Process XMLViews
foreach ($controllers as $ctrl) {
    $coreFile = __DIR__ . "/Core/XMLView/{$ctrl}.xml";
    $pluginFile = "$pluginDir/XMLView/{$ctrl}.xml";
    
    if (file_exists($coreFile)) {
        rename($coreFile, $pluginFile);
        echo "Moved XMLView: {$ctrl}.xml\n";
    }
}

// 4. Process Tables
foreach ($tables as $table) {
    $coreFile = __DIR__ . "/Core/Table/{$table}.xml";
    $pluginFile = "$pluginDir/Table/{$table}.xml";
    
    if (file_exists($coreFile)) {
        rename($coreFile, $pluginFile);
        echo "Moved Table: {$table}.xml\n";
    }
}

// 5. Create ini and Init.php
$ini = "name = '{$pluginName}'\ndescription = 'Core {$pluginName} Module'\nversion = 1.0\nmin_version = 2021\n";
file_put_contents("$pluginDir/facturascripts.ini", $ini);

$initPhp = "<?php\n\nnamespace FacturaScripts\\Plugins\\{$pluginName};\n\nclass Init extends \\FacturaScripts\\Core\\Base\\InitClass\n{\n    public function init(): void\n    {\n    }\n\n    public function update(): void\n    {\n    }\n}\n";
file_put_contents("$pluginDir/Init.php", $initPhp);

echo "Done extracting {$pluginName}.\n";
