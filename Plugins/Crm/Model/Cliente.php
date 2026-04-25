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
use FacturaScripts\Plugins\Trading\Model\Cliente as TradingCliente;

/**
 * The client CRM extension to handle contacts.
 */
class Cliente extends TradingCliente
{
    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->getDefaultAddress()->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    /**
     * Returns an array with the addresses associated with this customer.
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
    public function getDefaultAddress(string $type = 'billing'): Contacto
    {
        $contact = new Contacto();
        $id = $type === 'shipping' ? $this->idcontactoenv : $this->idcontactofact;
        $contact->load($id);
        return $contact;
    }

    protected function saveInsert(): bool
    {
        $return = parent::saveInsert();
        if ($return && empty($this->idcontactofact)) {
            $parts = explode(' ', $this->nombre);

            // creates new contact
            $contact = new Contacto();
            $contact->apellidos = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
            $contact->cifnif = $this->cifnif;
            $contact->codagente = $this->codagente;
            $contact->codcliente = $this->codcliente;
            $contact->descripcion = $this->nombre;
            $contact->email = $this->email;
            $contact->empresa = $this->razonsocial;
            $contact->fax = $this->fax;
            $contact->nombre = $parts[0];
            $contact->personafisica = $this->personafisica;
            $contact->telefono1 = $this->telefono1;
            $contact->telefono2 = $this->telefono2;
            $contact->tipoidfiscal = $this->tipoidfiscal;
            if ($contact->save()) {
                $this->idcontactofact = $contact->idcontacto;
                return $this->save();
            }
        }

        return $return;
    }
}
