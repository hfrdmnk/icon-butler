<?php

use App\Models\IconSet;
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
        Schema::create('icons', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(IconSet::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_logo')->default(false);
            $table->string('company_name')->nullable();
            $table->text('keywords')->nullable();
            $table->text('embedding')->nullable();
            $table->timestamps();

            $table->unique(['icon_set_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icons');
    }
};
