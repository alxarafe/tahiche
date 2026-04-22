<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('facturascli')) {
            Capsule::schema()->create('facturascli', function (Blueprint $table) {
                $table->id('idfactura');
                $table->string('codigo', 20)->unique();
                $table->string('codcliente', 10)->nullable();
                $table->string('nombre', 100)->nullable();
                $table->date('fecha');
                $table->string('codserie', 4);
                $table->string('codpago', 10)->nullable();
                $table->text('observaciones')->nullable();
                $table->double('neto', 15, 2)->default(0);
                $table->double('totaliva', 15, 2)->default(0);
                $table->double('totalrecargo', 15, 2)->default(0);
                $table->double('total', 15, 2)->default(0);
            });
        }

        if (!Capsule::schema()->hasTable('lineasfacturascli')) {
            Capsule::schema()->create('lineasfacturascli', function (Blueprint $table) {
                $table->id('idlinea');
                $table->integer('idfactura');
                $table->string('referencia', 100)->nullable();
                $table->string('descripcion', 255)->nullable();
                $table->double('cantidad', 15, 4)->default(1);
                $table->double('pvpunitario', 15, 6)->default(0);
                $table->double('dtopor', 15, 4)->default(0);
                $table->double('iva', 15, 4)->default(0);
                $table->double('pvptotal', 15, 6)->default(0);
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('lineasfacturascli');
        Capsule::schema()->dropIfExists('facturascli');
    }
};
