<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    private const TAIL_BYTES = 200 * 1024;

    public function index(): View
    {
        $this->authorizeAdmin();

        return view('admin.logs.index', [
            'logs' => $this->listLogs(),
            'tailBytes' => self::TAIL_BYTES,
            'tailBytesHuman' => $this->formatBytes(self::TAIL_BYTES),
        ]);
    }

    public function download(string $file): BinaryFileResponse
    {
        $this->authorizeAdmin();

        $path = $this->resolveLogPath($file);

        return response()->download($path, basename($path));
    }

    public function tail(string $file): Response
    {
        $this->authorizeAdmin();

        $path = $this->resolveLogPath($file);
        $content = $this->readLastBytes($path, self::TAIL_BYTES);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function listLogs(): Collection
    {
        $paths = glob(storage_path('logs/*.log')) ?: [];

        return collect($paths)
            ->map(fn (string $path) => new SplFileInfo($path))
            ->filter(fn (SplFileInfo $file) => $file->isFile())
            ->sortByDesc(fn (SplFileInfo $file) => $file->getMTime())
            ->values()
            ->map(function (SplFileInfo $file): array {
                $size = max(0, (int) $file->getSize());
                $mtime = (int) $file->getMTime();

                return [
                    'name' => $file->getFilename(),
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'modified_at' => $mtime > 0 ? date('Y-m-d H:i:s', $mtime) : '-',
                ];
            });
    }

    private function resolveLogPath(string $file): string
    {
        if (!preg_match('/^[A-Za-z0-9._-]+\.log$/', $file)) {
            abort(404);
        }

        $logsDir = realpath(storage_path('logs'));
        $path = realpath(storage_path('logs/' . $file));

        if ($logsDir === false || $path === false || !is_file($path)) {
            abort(404);
        }

        $normalizedLogsDir = rtrim(str_replace('\\', '/', $logsDir), '/') . '/';
        $normalizedPath = str_replace('\\', '/', $path);

        if (!str_starts_with(strtolower($normalizedPath), strtolower($normalizedLogsDir))) {
            abort(404);
        }

        return $path;
    }

    private function readLastBytes(string $path, int $maxBytes): string
    {
        $fileSize = filesize($path);
        if ($fileSize === false || $fileSize <= 0) {
            return '';
        }

        $bytesToRead = min($fileSize, $maxBytes);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            abort(500, 'Could not open log file.');
        }

        if ($bytesToRead < $fileSize) {
            fseek($handle, -$bytesToRead, SEEK_END);
        } else {
            rewind($handle);
        }

        $content = fread($handle, $bytesToRead);
        fclose($handle);

        if ($content === false) {
            return '';
        }

        return $content;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        $decimals = $value >= 10 ? 0 : 1;

        return number_format($value, $decimals, '.', '') . ' ' . $units[$unitIndex];
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);
    }
}
