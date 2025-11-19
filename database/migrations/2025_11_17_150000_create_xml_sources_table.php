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
        Schema::create('xml_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Kaynak adı (örn: "Supplier A", "Partner B")
            $table->string('url'); // XML URL
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_imported_at')->nullable();
            $table->integer('last_imported_count')->nullable(); // Son import edilen ürün sayısı
            $table->text('last_error')->nullable(); // Son hata mesajı
            $table->integer('import_interval_hours')->default(24); // Kaç saatte bir import edilecek
            $table->time('preferred_import_time')->nullable(); // Tercih edilen import saati
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_sources');
    }
};

