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
    Schema::create('stats', function (Blueprint $table) {
      $table->id();
      $table->foreignId('slug_id')->constrained()->cascadeOnDelete();
      $table->string('referer_host')->nullable();
      $table->string('country', 100)->nullable();
      $table->timestamp('clicked_at');
      $table->timestamps();

      $table->index('slug_id');
      $table->index(['slug_id', 'clicked_at']);
      $table->index(['slug_id', 'referer_host']);
      $table->index(['slug_id', 'country']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('stats');
  }
};
