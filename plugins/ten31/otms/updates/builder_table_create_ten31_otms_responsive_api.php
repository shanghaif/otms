<?php namespace ten31\Otms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateTen31OtmsResponsiveApi extends Migration
{
    public function up()
    {
        Schema::create('ten31_otms_responsive_api', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('position')->nullable();
            $table->string('company_name')->nullable();
            $table->string('telephone');
            $table->string('email');
            $table->integer('system')->unsigned();
            $table->text('note')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('ten31_otms_responsive_api');
    }
}
