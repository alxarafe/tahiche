<?php

use FacturaScripts\Core\Base\DataBase;

return new class {
    public function up(): void
    {
        $db = new DataBase();
        if (!$db->tableExists('facturascli')) {
            $db->exec("CREATE TABLE facturascli (
                idfactura INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(20) UNIQUE,
                codcliente VARCHAR(10) NULL,
                nombre VARCHAR(100) NULL,
                fecha DATE NOT NULL,
                codserie VARCHAR(4) NOT NULL,
                codpago VARCHAR(10) NULL,
                observaciones TEXT NULL,
                neto DOUBLE(15, 2) DEFAULT 0,
                totaliva DOUBLE(15, 2) DEFAULT 0,
                totalrecargo DOUBLE(15, 2) DEFAULT 0,
                total DOUBLE(15, 2) DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        if (!$db->tableExists('lineasfacturascli')) {
            $db->exec("CREATE TABLE lineasfacturascli (
                idlinea INT AUTO_INCREMENT PRIMARY KEY,
                idfactura INT NOT NULL,
                referencia VARCHAR(100) NULL,
                descripcion VARCHAR(255) NULL,
                cantidad DOUBLE(15, 4) DEFAULT 1,
                pvpunitario DOUBLE(15, 6) DEFAULT 0,
                dtopor DOUBLE(15, 4) DEFAULT 0,
                iva DOUBLE(15, 4) DEFAULT 0,
                pvptotal DOUBLE(15, 6) DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    }

    public function down(): void
    {
        $db = new DataBase();
        $db->exec("DROP TABLE IF EXISTS lineasfacturascli;");
        $db->exec("DROP TABLE IF EXISTS facturascli;");
    }
};
