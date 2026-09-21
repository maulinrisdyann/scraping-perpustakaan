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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->cascadeOnDelete();
            $table->foreignId('tracked_url_id')->constrained('tracked_urls')->cascadeOnDelete();
            $table->string('external_review_id')->unique(); // fingerprint unik, kunci idempotency
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('review_text')->nullable();
            $table->string('review_relative_time')->nullable(); // teks asli dari GMaps, mis. "2 minggu lalu"
            $table->timestamp('review_date')->nullable(); // hasil resolve relative_time, bisa null kalau ambigu
            $table->string('sentiment_label')->nullable(); // diisi Fase 3
            $table->float('sentiment_score')->nullable();
            $table->timestamp('scraped_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
