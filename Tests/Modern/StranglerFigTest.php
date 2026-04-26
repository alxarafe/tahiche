<?php

namespace Tahiche\Tests\Modern;

use PHPUnit\Framework\TestCase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Controller\Files;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\KernelException;

class StranglerFigTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testDinamicFolderIsDead()
    {
        $dinamicPath = FS_FOLDER . '/Dinamic';
        $this->assertFalse(
            is_dir($dinamicPath), 
            'La carpeta Dinamic/ sigue existiendo físicamente, lo cual viola el patrón Estrangulador.'
        );
    }

    public function testXmlViewCacheIsActive()
    {
        $xmlViewPath = FS_FOLDER . '/var/cache/xmlview';
        $this->assertTrue(
            is_dir($xmlViewPath), 
            'La carpeta var/cache/xmlview/ no fue creada por el PluginsDeploy.'
        );

        $files = glob($xmlViewPath . '/*.xml');
        $this->assertNotEmpty($files, 'La caché de XMLView está vacía, PluginsDeploy falló al guardar los archivos.');
    }

    public function testAssetsCacheIsActive()
    {
        $assetsPath = FS_FOLDER . '/var/cache/assets/Assets/CSS';
        $this->assertTrue(
            is_dir($assetsPath), 
            'La carpeta var/cache/assets/Assets/CSS no existe, los assets estáticos no se están copiando a la caché.'
        );

        $files = glob($assetsPath . '/*.css');
        $this->assertNotEmpty($files, 'La caché de CSS está vacía.');
    }

    public function testTranslationCacheIsActive()
    {
        $translationPath = FS_FOLDER . '/var/cache/translation';
        $this->assertTrue(
            is_dir($translationPath), 
            'La carpeta var/cache/translation no existe, las traducciones no se están cacheando.'
        );

        $files = glob($translationPath . '/*.json');
        $this->assertNotEmpty($files, 'La caché de traducciones está vacía.');
    }

    public function testFilesControllerVirtualRouting()
    {
        // En lugar de llamar a run(), instanciamos el controlador que es donde se define el path
        // Usamos una ruta de asset conocida (del core) pero como si la pidiera un plugin apuntando a Dinamic.
        $url = '/Dinamic/Assets/Images/favicon.ico';
        
        try {
            // El controlador Files lanzará excepción si no encuentra el archivo real,
            // pero si la redirección interna funciona (var/cache/assets/...), pasará silenciosamente.
            $filesController = new Files('Files', $url);
            
            // Accedemos a la propiedad privada filePath por reflexión
            $reflection = new \ReflectionClass($filesController);
            $property = $reflection->getProperty('filePath');
            $property->setAccessible(true);
            $actualFilePath = $property->getValue($filesController);
            
            $expectedFilePath = FS_FOLDER . '/var/cache/assets/Assets/Images/favicon.ico';
            $this->assertEquals($expectedFilePath, $actualFilePath, 'FilesController no está redirigiendo correctamente Dinamic/ a var/cache/assets/.');
            
        } catch (KernelException $e) {
            $this->fail('FilesController lanzó excepción al intentar acceder a un asset virtualizado de Dinamic: ' . $e->getMessage());
        }
    }

    public function testHtmlViewCacheRouting()
    {
        // Html.php define las rutas. La validamos verificando que la carga de plantillas Twig pueda encontrar las cacheadas.
        $path = FS_DEBUG ? FS_FOLDER . '/Core/View' : FS_FOLDER . '/var/cache/assets/View';
        
        // Simplemente verificamos que el path existe
        $this->assertTrue(
            is_dir($path),
            'El path de vistas (Core/View o var/cache/assets/View) no existe, el motor Twig fallará.'
        );
    }
}
