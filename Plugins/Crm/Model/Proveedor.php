<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace FacturaScripts\Plugins\Crm\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Plugins\Crm\Model\Contacto;
use FacturaScripts\Plugins\Trading\Model\Proveedor as TradingProveedor;

/**
 * A supplier extension for CRM to add Contact relations.
 */
class Proveedor extends TradingProveedor
{
    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->getDefaultAddress()->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    /**
     * Returns the addresses associated with this supplier.
     *
     * @return Contacto[]
     */
    public function getAddresses(): array
    {
        $where = [new DataBaseWhere($this->primaryColumn(), $this->id())];
        return Contacto::all($where, [], 0, 0);
    }

    /**
     * Return the default billing or shipping address.
     *
     * @return Contacto
     */
    public function getDefaultAddress(): Contacto
    {
        $contact = new Contacto();
        $contact->load($this->idcontacto);
        return $contact;
    }

    protected function saveInsert(): bool
    {
        $return = parent::saveInsert();
        if ($return && empty($this->idcontacto)) {
            // creates new contact
            $contact = new Contacto();
            $contact->cifnif = $this->cifnif;
            $contact->codproveedor = $this->codproveedor;
            $contact->descripcion = $this->nombre;
            $contact->email = $this->email;
            $contact->empresa = $this->razonsocial;
            $contact->fax = $this->fax;
            $contact->nombre = $this->nombre;
            $contact->personafisica = $this->personafisica;
            $contact->telefono1 = $this->telefono1;
            $contact->telefono2 = $this->telefono2;
            $contact->tipoidfiscal = $this->tipoidfiscal;
            if ($contact->save()) {
                $this->idcontacto = $contact->idcontacto;
                return $this->save();
            }
        }

        return $return;
    }
}
