<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eavesdrops', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('channel')->nullable();
            $table->string('to')->nullable();
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->string('notifiable_type')->nullable();
            $table->string('notifiable_id')->nullable();
            $table->string('notification')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }
};
