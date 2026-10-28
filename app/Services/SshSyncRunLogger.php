<?php
namespace App\Services;

use Illuminate\Support\Facades\File;

class SshSyncRunLogger
{
    private string $runId;
    private string $path;
    private array $events = [];

    public function __construct(?string $runId = null)
    {
        $this->runId = $runId ?: date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $dir = storage_path('app/ssh-sync-runs');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0775, true);
        }
        $this->path = $dir . '/sync-' . $this->runId . '.json';
        $this->write();
    }

    public function id(): string
    {
        return $this->runId;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function add(string $level, string $message, array $context = []): void
    {
        $this->events[] = [
            'ts' => now()->format('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        $this->write();
    }

    public function summary(): array
    {
        return [
            'run_id' => $this->runId,
            'events' => $this->events,
            'path' => $this->path,
        ];
    }

    private function write(): void
    {
        file_put_contents($this->path, json_encode($this->summary(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
