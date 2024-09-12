<?php

use App\Models\Icon;
use App\Models\Suggestion;
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
        Schema::create('icon_suggestion', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Suggestion::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Icon::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icon_suggestion');
    }
};
