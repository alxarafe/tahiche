<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Internal;

use Exception;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Translator;
use FacturaScripts\Dinamic\Model\Page;

final class PluginsDeploy
{
    /** @var array */
    private static $enabledPlugins = [];

    /** @var array */
    private static $fileList = [];

    /** @var array */
    private static $pages = [];

    public static function initControllers(): void
    {
        $files = [];
        foreach (Tools::folderScan(Tools::folder('Core', 'Controller')) as $file) {
            $files[$file] = $file;
        }
        foreach (self::$enabledPlugins as $pluginName) {
            foreach (Tools::folderScan(Tools::folder('Plugins', $pluginName, 'Controller')) as $file) {
                $files[$file] = $file;
            }
        }
        foreach ($files as $fileName) {
            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            // excluimos Installer y los que comienzan por Api
            $controllerName = basename($fileName, '.php');
            if ($controllerName === 'Installer' || str_starts_with($controllerName, 'Api')) {
                continue;
            }

            // validamos el nombre del controlador para evitar el path traversal
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $controllerName)) {
                Tools::log()->warning('Invalid controller name: ' . $controllerName);
                continue;
            }

            $controllerNamespace = '\\FacturaScripts\\Dinamic\\Controller\\' . $controllerName;
            Tools::log()->debug('Loading controller: ' . $controllerName);

            if (!class_exists($controllerNamespace)) {
                Tools::log()->warning('Controller not found: ' . $controllerNamespace);
                continue;
            }

            try {
                $controller = new $controllerNamespace($controllerName);
                self::loadPage($controller->getPageData());
            } catch (Exception $exc) {
                Tools::log()->critical('cant-load-controller', ['%controllerName%' => $controllerName]);
                Tools::log()->critical($exc->getMessage());
            }
        }

        self::removeOldPages();

        // comprobamos la página de inicio de la aplicación
        $saveSettings = false;

        $homePage = Tools::settings('default', 'homepage', '');
        if (!in_array($homePage, self::$pages)) {
            Tools::settingsSet('default', 'homepage', 'AdminPlugins');
            $saveSettings = true;
        }

        if ($saveSettings) {
            Tools::settingsSave();
        }
    }

    public static function run(array $enabledPlugins, bool $clean = true): void
    {
        self::$enabledPlugins = array_reverse($enabledPlugins);
        self::$fileList = [];

        $folders = ['Assets', 'Controller', 'Data', 'Error', 'Lib', 'Model', 'Table', 'View', 'Worker', 'XMLView'];
        // Limpiamos también la caché moderna de XMLView y Assets
        if ($clean) {
            Tools::folderDelete(FS_FOLDER . '/var/cache/xmlview');
            Tools::folderDelete(FS_FOLDER . '/var/cache/assets');
            Tools::folderDelete(FS_FOLDER . '/Dinamic'); // Extirpamos Dinamic en cada limpieza
            @unlink(FS_FOLDER . '/var/cache/class_resolver.php');
        }

        foreach ($folders as $folder) {
            // Creamos las carpetas base en var/cache/assets en lugar de Dinamic
            if ($folder !== 'XMLView') {
                $cacheDir = FS_FOLDER . '/var/cache/assets/' . $folder;
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0777, true);
                }
            }

            // examinamos los plugins
            foreach (self::$enabledPlugins as $pluginName) {
                // enlazamos los archivos del plugin, si existe
                if (file_exists(Tools::folder('Plugins', $pluginName, $folder))) {
                    self::linkFiles($folder, 'Plugins', $pluginName);
                }
            }

            // examinamos el núcleo
            if (file_exists(Tools::folder('Core', $folder))) {
                self::linkFiles($folder);
            }
        }

        // recargamos las traducciones
        Translator::deploy();
        Translator::reload();
    }

    private static function extensionSupport(string $namespace): bool
    {
        return $namespace === 'FacturaScripts\Dinamic\Controller';
    }

    private static function getClassType(string $fileName, string $folder, string $place, string $pluginName): string
    {
        $path = empty($pluginName) ?
            Tools::folder($place, $folder, $fileName) :
            Tools::folder($place, $pluginName, $folder, $fileName);

        if (!file_exists($path)) {
            throw new Exception('Unable to locate plugin class: ' . $fileName . ' on ' . $path);
        }

        if (!is_file($path)) {
            throw new Exception('Path is not a file: ' . $path);
        }

        if (!is_readable($path)) {
            throw new Exception('File is not readable: ' . $path);
        }

        $content = file_get_contents($path);
        $tokens = token_get_all($content);

        $isAbstract = false;
        $foundClass = false;

        foreach ($tokens as $i => $token) {
            // Ignoramos los tokens que no son arrays (como ; o {)
            if (!is_array($token)) {
                continue;
            }

            // Si encontramos la palabra clave abstract
            if ($token[0] === T_ABSTRACT) {
                $isAbstract = true;
            }

            // Si encontramos la palabra clave class después de abstract (o sin abstract)
            if ($token[0] === T_CLASS) {
                $foundClass = true;
                break;
            }

            // Si encontramos otra palabra clave estructural, reseteamos la variable abstract
            if (in_array($token[0], [T_FUNCTION, T_INTERFACE, T_TRAIT])) {
                $isAbstract = false;
            }
        }

        if (!$foundClass) {
            return '';
        }

        return $isAbstract ? 'abstract class' : 'class';
    }

    private static function linkFile(string $fileName, string $folder, string $filePath): void
    {
        $path = FS_FOLDER . '/var/cache/assets/' . $folder . '/' . $fileName;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!copy($filePath, $path)) {
            throw new Exception('Failed to copy file: ' . $filePath . ' to ' . $path);
        }

        self::$fileList[$folder][$fileName] = $fileName;
    }

    private static function linkFiles(string $folder, string $place = 'Core', string $pluginName = ''): void
    {
        $path = empty($pluginName) ?
            Tools::folder($place, $folder) :
            Tools::folder($place, $pluginName, $folder);

        foreach (Tools::folderScan($path, true) as $fileName) {
            if (isset(self::$fileList[$folder][$fileName])) {
                continue;
            }

            $fileInfo = pathinfo($fileName);
            $filePath = Tools::folder($path, $fileName);

            if (is_dir($filePath)) {
                $cacheDir = FS_FOLDER . '/var/cache/assets/' . $folder . '/' . $fileName;
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0777, true);
                }
                continue;
            } elseif ($fileInfo['filename'] === '' || !is_file($filePath)) {
                continue;
            }

            $extension = $fileInfo['extension'] ?? '';
            switch ($extension) {
                case 'php':
                    self::linkPHPFile($fileName, $folder, $place, $pluginName);
                    break;

                case 'xml':
                    self::linkXMLFile($fileName, $folder, $filePath);
                    break;

                default:
                    self::linkFile($fileName, $folder, $filePath);
            }
        }
    }

    private static function linkPHPFile(string $fileName, string $folder, string $place, string $pluginName): void
    {
        // En lugar de generar físicamente los archivos en Dinamic/, confiamos en ClassResolver
        // para mapear dinámicamente las clases legacy a sus nuevas ubicaciones.
        // Mantenemos el registro de la clase en fileList por si otros procesos internos lo usan.
        self::$fileList[$folder][$fileName] = $fileName;
    }

    private static function linkXMLFile(string $fileName, string $folder, string $originPath): void
    {
        // Buscamos las extensiones
        $extensions = [];
        foreach (self::$enabledPlugins as $pluginName) {
            $extensionPath = Tools::folder('Plugins', $pluginName, 'Extension', $folder, $fileName);
            if (file_exists($extensionPath)) {
                $extensions[] = $extensionPath;
            }
        }

        // Fusionamos los archivos XML
        $xml = simplexml_load_file($originPath);
        if (false === $xml) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown error';
            throw new Exception('Unable to load XML file: ' . $originPath . ' - ' . $errorMsg);
        }

        foreach ($extensions as $extension) {
            $xmlExtension = simplexml_load_file($extension);
            if ($xmlExtension === false) {
                $errors = libxml_get_errors();
                $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown error';
                throw new Exception('Unable to load XML extension file: ' . $extension . ' - ' . $errorMsg);
            }

            self::mergeXMLDocs($xml, $xmlExtension);
        }

        // Guardamos en la nueva caché moderna
        $destinationDir = FS_FOLDER . '/var/cache/xmlview';
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }
        $destinationPath = $destinationDir . '/' . $fileName;
        if ($xml->asXML($destinationPath) === false) {
            throw new Exception('Unable to write XML file: ' . $destinationPath);
        }

        self::$fileList[$folder][$fileName] = $fileName;
    }

    private static function loadPage(array $data): void
    {
        if (empty($data)) {
            return;
        }

        // añadimos a la lista de páginas
        self::$pages[] = $data['name'];

        // comprobamos si ya existe la página
        $page = new Page();
        if (false === $page->load($data['name'])) {
            $data['ordernum'] = 100;
            $page->loadFromData($data);
            $page->save();
            return;
        }

        // comprobamos si hay que actualizar la página
        $need = $page->menu !== $data['menu'] ||
            $page->submenu !== $data['submenu'] ||
            $page->title !== $data['title'] ||
            $page->icon !== $data['icon'] ||
            $page->showonmenu !== $data['showonmenu'];

        if ($need) {
            $page->loadFromData($data);
            $page->save();
        }
    }

    private static function mergeXMLDocs(&$source, $extension): void
    {
        foreach ($extension->children() as $extChild) {
            // necesitamos $num para saber qué número de elemento del dom sobreescribir
            $num = -1;

            $found = false;
            foreach ($source->children() as $child) {
                if ($child->getName() == $extChild->getName()) {
                    $num++;
                }

                if (!self::mergeXMLDocsCompare($child, $extChild)) {
                    continue;
                }

                // Elemento encontrado. ¿Sobrescribir o añadir hijos? Solo para padres, por ejemplo, group, etc.
                $found = true;
                $extDom = dom_import_simplexml($extChild);
                if ($extDom === false) {
                    throw new Exception('Failed to convert SimpleXML to DOM');
                }

                switch (mb_strtolower($extDom->getAttribute('overwrite'))) {
                    case 'true':
                        $sourceDom = dom_import_simplexml($source);
                        if ($sourceDom === false) {
                            throw new Exception('Failed to convert SimpleXML source to DOM');
                        }

                        $newElement = $sourceDom->ownerDocument->importNode($extDom, true);
                        $targetNode = $sourceDom->getElementsByTagName($newElement->nodeName)->item($num);
                        if ($targetNode === null) {
                            throw new Exception('Target node not found for replacement');
                        }

                        $sourceDom->replaceChild($newElement, $targetNode);
                        break;

                    default:
                        self::mergeXMLDocs($child, $extChild);
                }
                break;
            }

            // Elemento no encontrado. Añadir todo o reemplazar hijo, solo para hijos, por ejemplo, widget, etc.
            if (!$found) {
                $sourceDom = dom_import_simplexml($source);
                if ($sourceDom === false) {
                    throw new Exception('Failed to convert SimpleXML source to DOM');
                }

                $extDom = dom_import_simplexml($extChild);
                if ($extDom === false) {
                    throw new Exception('Failed to convert SimpleXML extension to DOM');
                }

                $newElement = $sourceDom->ownerDocument->importNode($extDom, true);

                switch (mb_strtolower($extDom->getAttribute('overwrite'))) {
                    case 'true':
                        // Solo podemos reemplazar nodos hijos directos, no todos los descendientes
                        if ($num >= 0 && $num < $sourceDom->childNodes->length) {
                            $targetNode = $sourceDom->childNodes->item($num);
                            if ($targetNode !== null && $targetNode->parentNode === $sourceDom) {
                                $sourceDom->replaceChild($newElement, $targetNode);
                            } else {
                                // Si no es un hijo directo, simplemente lo añadimos
                                $sourceDom->appendChild($newElement);
                            }
                        } else {
                            // Si el índice es inválido, añadimos el elemento
                            $sourceDom->appendChild($newElement);
                        }
                        break;

                    default:
                        $sourceDom->appendChild($newElement);
                        break;
                }
            }
        }
    }

    private static function mergeXMLDocsCompare($source, $extension): bool
    {
        if ($source->getName() != $extension->getName()) {
            return false;
        }

        foreach ($extension->attributes() as $extAttr => $extAttrValue) {
            // Usamos el nombre como identificador excepto con la fila, que se identifica por el tipo
            if ($extAttr != 'name' && $extension->getName() != 'row') {
                continue;
            } elseif ($extAttr != 'type' && $extension->getName() == 'row') {
                continue;
            }

            foreach ($source->attributes() as $attr => $attrValue) {
                if ($attr == $extAttr) {
                    return (string)$extAttrValue == (string)$attrValue;
                }
            }
        }

        return in_array($extension->getName(), ['columns', 'modals', 'rows']);
    }

    private static function removeOldPages(): void
    {
        // eliminamos las páginas que ya no existen
        foreach (Page::all() as $page) {
            if (false === in_array($page->name, self::$pages, true)) {
                $page->delete();
            }
        }
    }
}
