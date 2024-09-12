<?php

namespace App\Jobs;

use App\Models\Icon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class GenerateIconEmbedding implements ShouldQueue
{
    use Queueable;

    protected $icon;

    /**
     * Create a new job instance.
     */
    public function __construct(Icon $icon)
    {
        $this->icon = $icon;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $this->icon->name.' '.($this->icon->keywords ? implode(' ', json_decode($this->icon->keywords, true)) : ''),
            ]);

            $embedding = $response->embeddings[0]->embedding;
            $this->icon->update(['embedding' => json_encode($embedding)]);
        } catch (\Exception $e) {
            Log::error('Error generating icon embedding', [
                'icon_id' => $this->icon->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
