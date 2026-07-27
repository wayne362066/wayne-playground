<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wish_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wish_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('from_value')->nullable();
            $table->string('to_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wish_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wish_events');
    }
};
