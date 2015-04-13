<?php namespace Feegleweb\OctoshopLite\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProductsTable extends Migration
{

    public function up()
    {
        Schema::create('feegleweb_octoshop_products', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title')->index();
            $table->string('slug')->index()->unique();
            $table->longText('description');
            $table->string('model')->nullable();
            $table->decimal('price', 10, 2)->default(0)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_stockable')->default(false);
            $table->integer('stock')->default(0)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('feegleweb_octoshop_products');
    }
}
