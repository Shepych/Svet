<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('triggers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->references('id')
                ->on('users');

            $table->text('event')->nullable();
            $table->text('thoughts')->nullable();
            $table->text('feeling')->nullable();
            $table->text('action')->nullable();
            $table->text('thinking')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('triger');
    }
};
