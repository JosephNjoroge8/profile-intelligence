<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            // UUID v7 primary key — sortable by time, no auto-increment needed
            $table->uuid('id')->primary();

            // Name is the idempotency key — unique constraint enforced at DB level
            $table->string('name')->unique();

            // Genderize fields (count is renamed to sample_size per spec)
            $table->string('gender', 20)->nullable();
            $table->decimal('gender_probability', 5, 4)->nullable();
            $table->unsignedInteger('sample_size')->nullable();

            // Agify fields
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('age_group', 20)->nullable();

            // Nationalize fields — top country by probability
            $table->string('country_id', 10)->nullable();
            $table->decimal('country_probability', 5, 4)->nullable();

            // Manual UTC timestamp — no updated_at needed
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
