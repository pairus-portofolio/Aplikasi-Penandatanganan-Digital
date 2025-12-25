<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->integer('width')->nullable()->after('posisi_y'); 
            $table->integer('height')->nullable()->after('width');
        });
    }

    public function down()
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });
    }
};
