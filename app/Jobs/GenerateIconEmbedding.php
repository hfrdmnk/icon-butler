<?php

namespace App\Jobs;

use App\Models\Icon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;

class GenerateIconEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $icon;

    public function __construct(Icon $icon)
    {
        $this->icon = $icon;
    }

    public function handle()
    {
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $this->icon->name,
        ]);

        $embedding = $response->embeddings[0]->embedding;
        $this->icon->update(['embedding' => json_encode($embedding)]);
    }
}
