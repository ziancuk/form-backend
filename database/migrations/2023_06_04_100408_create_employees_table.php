<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 100)->nullable();
            $table->string('firstname', 60);
            $table->string('lastname', 60)->nullable();
            $table->string('email', 30);
            $table->string('phone', 13);
            $table->string('country', 30);
            $table->string('title', 30);
            $table->string('picture', 100);
            $table->string('city', 30);
            $table->text('address');
            $table->bigInteger('pos');
            $table->string('driving', 2);
            $table->string('nationality', 30);
            $table->string('place', 30);
            $table->date('birthdate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
