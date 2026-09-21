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
        Schema::create('tracked_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->cascadeOnDelete();
            $table->string('label'); // nama tempat/akun, ditampilkan di dashboard
            $table->text('search_query')->nullable(); // query pencarian (GMaps: search-based, bukan url place langsung)
            $table->text('url')->nullable(); // url postingan (IG/FB) atau url referensi GMaps
            $table->string('place_identifier')->nullable(); // CID/place_id GMaps buat validasi hasil search
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracked_urls');
    }
};
