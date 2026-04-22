<?php
/**
 * This file is part of Tahiche
 * Copyright (C) 2022-2025 Tahiche Team <tahiche@alxarafe.com>
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

namespace Tahiche\Test\Core\Lib;

use Tahiche\Core\Base\DataBase;
use Tahiche\Core\DataSrc\Paises;
use Tahiche\Core\Lib\Accounting\AccountingAccounts;
use Tahiche\Core\Lib\Accounting\AccountingCreation;
use Tahiche\Core\Model\Cuenta;
use Tahiche\Core\Model\Ejercicio;
use Tahiche\Core\Model\Subcuenta;
use Tahiche\Test\Traits\DefaultSettingsTrait;
use Tahiche\Test\Traits\LogErrorsTrait;
use Tahiche\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AccountingCreationTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    private static $subaccounts = [];

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCreateCustomer(): void
    {
        // comprobamos que las tablas existen
        $db = new DataBase();
        $this->assertTrue($db->tableExists(Cuenta::tableName()));
        $this->assertTrue($db->tableExists(Subcuenta::tableName()));

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // obtenemos la cuenta de clientes
        $accounts = new AccountingAccounts();
        $accounts->exercise = $this->getCurrentExercise();
        $this->assertTrue($accounts->exercise->exists());
        $customersAccount = $accounts->getSpecialAccount(AccountingAccounts::SPECIAL_CUSTOMER_ACCOUNT);
        $this->assertTrue($customersAccount->exists(), 'cant-get-customer-account');

        // obtenemos una nueva subcuenta para el cliente, 1001 veces (solo España),
        // para comprobar si en todos los casos se crea una nueva
        $creator = new AccountingCreation();
        $max = Paises::default()->codpais === 'ESP' ? 1000 : 10;
        for ($i = 0; $i < $max; $i++) {
            $subaccount = $creator->createSubjectAccount($customer, $customersAccount);
            $this->assertTrue($subaccount->exists(), 'cant-create-customer-subaccount-' . $i);

            $customer->codsubcuenta = null;
            self::$subaccounts[] = $subaccount;
        }

        // eliminamos el cliente
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    private function getCurrentExercise(): Ejercicio
    {
        $ejercicioModel = new Ejercicio();
        foreach ($ejercicioModel->all() as $ejercicio) {
            if ($ejercicio->isOpened()) {
                return $ejercicio;
            }
        }

        return $ejercicioModel;
    }

    protected function tearDown(): void
    {
        $this->logErrors();

        // eliminamos las subcuentas creadas
        foreach (self::$subaccounts as $key => $subaccount) {
            $subaccount->delete();
            unset(self::$subaccounts[$key]);
        }
    }
}
