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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('login');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('code')->nullable();
            $table->timestamp('code_sent_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('remember_token')->nullable();
            $table->timestamp('remember_sent_at')->nullable();
            $table->integer('remember_attempts')->default(0);
            $table->string('api_token')->nullable();
            $table->json('viewed_articles')->default('[]');

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
        Schema::dropIfExists('users');
    }
};
