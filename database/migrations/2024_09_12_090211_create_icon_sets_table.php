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
        Schema::create('icon_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('url');
            $table->timestamps();
        });

        IconSet::firstOrCreate([
            'name' => 'phosphor',
            'display_name' => 'Phosphor Icons',
            'url' => 'https://phosphoricons.com',
        ]);

        IconSet::firstOrCreate([
            'name' => 'lucide',
            'display_name' => 'Lucide Icons',
            'url' => 'https://lucide.dev',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icon_sets');
    }
};
