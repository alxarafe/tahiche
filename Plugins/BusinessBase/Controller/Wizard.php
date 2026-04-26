<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\BusinessBase\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\User;

/**
 * Wizard for initial company setup (BusinessBase plugin).
 * Handles company data, address, logo, and admin password.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Wizard extends Controller
{
    const ITEM_SELECT_LIMIT = 500;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'wizard';
        $data['icon'] = 'fa-solid fa-wand-magic-sparkles';
        $data['showonmenu'] = true;
        return $data;
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     * @param bool $addEmpty
     *
     * @return array
     */
    public function getSelectValues(string $modelName, bool $addEmpty = false): array
    {
        $values = $addEmpty ? ['' => '------'] : [];
        $modelClassName = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        if (false === class_exists($modelClassName)) {
            return $values;
        }

        $model = new $modelClassName();

        $order = [$model->primaryDescriptionColumn() => 'ASC'];
        foreach ($model->all([], $order, 0, self::ITEM_SELECT_LIMIT) as $newModel) {
            $values[$newModel->primaryColumnValue()] = $newModel->primaryDescription();
        }

        return $values;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->inputOrQuery('action', '');
        switch ($action) {
            case 'step1':
                $this->saveStep1();
                break;

            default:
                if (empty($this->empresa->email) && $this->user->email) {
                    $this->empresa->email = $this->user->email;
                    $this->empresa->save();
                }
        }
    }

    /**
     * Initialize required models.
     *
     * @param array $names
     */
    private function initModels(array $names): void
    {
        foreach ($names as $name) {
            $className = '\\FacturaScripts\\Dinamic\\Model\\' . $name;
            if (class_exists($className)) {
                new $className();
            }
        }
    }

    /**
     * Set default AppSettings based on codpais
     *
     * @param string $codpais
     */
    private function preSetAppSettings(string $codpais): void
    {
        $filePath = FS_FOLDER . '/var/cache/assets/Data/Codpais/' . $codpais . '/default.json';
        if (file_exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            $defaultValues = json_decode($fileContent, true) ?? [];
            foreach ($defaultValues as $group => $values) {
                foreach ($values as $key => $value) {
                    Tools::settingsSet($group, $key, $value);
                }
            }
        }

        Tools::settingsSet('default', 'codpais', $codpais);
        Tools::settingsSet('default', 'homepage', 'Root');
        Tools::settingsSave();
    }

    /**
     * Save company default address.
     *
     * @param string $codpais
     */
    private function saveAddress(string $codpais): void
    {
        $this->empresa->apartado = $this->request->input('apartado', '');
        $this->empresa->cifnif = $this->request->input('cifnif', '');
        $this->empresa->ciudad = $this->request->input('ciudad', '');
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $this->request->input('codpostal', '');
        $this->empresa->direccion = $this->request->input('direccion', '');
        $this->empresa->nombre = $this->request->input('empresa', '');
        $this->empresa->nombrecorto = Tools::textBreak($this->empresa->nombre, 32);
        $this->empresa->personafisica = (bool)$this->request->input('personafisica', '0');
        $this->empresa->provincia = $this->request->input('provincia', '');
        $this->empresa->telefono1 = $this->request->input('telefono1', '');
        $this->empresa->telefono2 = $this->request->input('telefono2', '');
        $this->empresa->tipoidfiscal = $this->request->input('tipoidfiscal', '');
        if (empty($this->empresa->tipoidfiscal)) {
            $this->empresa->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
        }
        $this->empresa->save();

        // assigns warehouse?
        if (class_exists('\\FacturaScripts\\Dinamic\\Model\\Almacen')) {
            $where = [
                \FacturaScripts\Core\Where::eq('idempresa', $this->empresa->idempresa),
                \FacturaScripts\Core\Where::orIsNull('idempresa')
            ];
            foreach (\FacturaScripts\Dinamic\Model\Almacen::all($where) as $almacen) {
                $this->setWarehouse($almacen, $codpais);
                return;
            }

            // no assigned warehouse? Create a new one
            $almacen = new \FacturaScripts\Dinamic\Model\Almacen();
            $this->setWarehouse($almacen, $codpais);
        } else {
            // Save idempresa even if Almacen is disabled
            \FacturaScripts\Core\Tools::settingsSet('default', 'idempresa', $this->empresa->idempresa);
            \FacturaScripts\Core\Tools::settingsSave();
        }
    }

    private function saveEmail(string $email): bool
    {
        if (empty($this->empresa->email)) {
            $this->empresa->email = $email;
        }

        if (empty($this->user->email)) {
            $this->user->email = $email;
        }

        return $this->empresa->save() && $this->user->save();
    }

    /**
     * Save the new password if data is admin admin
     *
     * @return bool Returns true if success, otherwise return false.
     */
    private function saveNewPassword(string $pass): bool
    {
        $this->user->newPassword = $pass;
        $this->user->newPassword2 = $this->request->input('repassword', '');
        return $this->user->save();
    }

    private function saveStep1(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $codpais = $this->request->input('codpais', $this->empresa->codpais);
        $this->preSetAppSettings($codpais);

        $this->initModels(['AttachedFile', 'Diario', 'EstadoDocumento', 'FormaPago',
            'Impuesto', 'Retencion', 'Serie', 'Provincia']);
        $this->saveAddress($codpais);

        if (false === $this->saveLogo()) {
            return;
        }

        // change password
        $pass = $this->request->input('password', '');
        if ('' !== $pass && false === $this->saveNewPassword($pass)) {
            return;
        }

        // change email
        $email = $this->request->input('email', '');
        if ('' !== $email && false === $this->saveEmail($email)) {
            return;
        }

        // finalize: deploy and redirect to dashboard
        $this->finalizeWizard();
    }

    /**
     * Finalize the wizard: deploy plugins, set homepage, and redirect.
     */
    private function finalizeWizard(): void
    {
        // load all models
        $modelNames = [];
        foreach (Tools::folderScan(Tools::folder('Core', 'Model')) as $fileName) {
            if (substr($fileName, -4) === '.php') {
                $modelNames[] = substr($fileName, 0, -4);
            }
        }
        foreach (Plugins::enabled() as $pluginName) {
            foreach (Tools::folderScan(Tools::folder('Plugins', $pluginName, 'Model')) as $fileName) {
                if (substr($fileName, -4) === '.php') {
                    $modelNames[] = substr($fileName, 0, -4);
                }
            }
        }
        if (false === $this->dataBase->tableExists('fs_users')) {
            $this->initModels($modelNames);
        }

        // load controllers
        Plugins::deploy(true, true);

        // set default role
        if (class_exists('\\FacturaScripts\\Dinamic\\Model\\Role')) {
            $role = new \FacturaScripts\Dinamic\Model\Role();
            if ($role->load('employee')) {
                Tools::settingsSet('default', 'codrole', $role->codrole);
                Tools::settingsSave();
            }
        }

        // change user homepage
        $this->user->homepage = $this->dataBase->tableExists('fs_users')
            && class_exists('\\FacturaScripts\\Dinamic\\Controller\\AdminPlugins')
            ? 'AdminPlugins'
            : 'Dashboard';
        $this->user->save();

        // redirect to the home page
        $this->redirect($this->user->homepage, 2);
    }

    private function saveLogo(): bool
    {
        $uploadFile = $this->request->file('logo');
        if (empty($uploadFile)) {
            return true;
        }

        if (false === $uploadFile->isValid()) {
            Tools::log()->error($uploadFile->getErrorMessage());
            return false;
        }

        if (false === in_array($uploadFile->getClientMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            Tools::log()->error('not-valid-image');
            return false;
        }

        $attachedFile = $this->uploadLogoFile($uploadFile);
        if (empty($attachedFile->idfile)) {
            Tools::log()->error('file-not-found', ['%fileName%' => $uploadFile->getClientOriginalName()]);
            return false;
        }

        $this->empresa->idlogo = $attachedFile->idfile;
        return $this->empresa->save();
    }

    private function setWarehouse($almacen, string $codpais): void
    {
        $almacen->ciudad = $this->empresa->ciudad;
        $almacen->codpais = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre = $this->empresa->nombrecorto;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        Tools::settingsSet('default', 'codalmacen', $almacen->codalmacen);
        Tools::settingsSet('default', 'idempresa', $this->empresa->idempresa);
        Tools::settingsSave();
    }

    private function uploadLogoFile(UploadedFile $uploadFile)
    {
        // exclude php files
        if (in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
            return new AttachedFile();
        }

        $destiny = FS_FOLDER . '/MyFiles/';
        $destinyName = $uploadFile->getClientOriginalName();
        if (file_exists($destiny . $destinyName)) {
            $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
        }

        if (false === $uploadFile->move($destiny, $destinyName)) {
            return new AttachedFile();
        }

        $file = new AttachedFile();
        $file->path = $destinyName;
        return $file->save() ? $file : new AttachedFile();
    }
}
