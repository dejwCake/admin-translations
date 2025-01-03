<?php

declare(strict_types=1);

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
        Schema::create('translations', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('namespace')->default('*');
            $table->index('namespace');
            $table->string('group');
            $table->index('group');
            $table->text('key');
            $table->jsonb('text');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('translations');
    }
};
