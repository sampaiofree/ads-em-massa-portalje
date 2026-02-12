<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

afterEach(function () {
    $paths = glob(storage_path('logs/test-log-*.log')) ?: [];

    foreach ($paths as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
});

function createTestLog(string $content): string
{
    File::ensureDirectoryExists(storage_path('logs'));

    $name = 'test-log-' . Str::lower((string) Str::uuid()) . '.log';
    File::put(storage_path('logs/' . $name), $content);

    return $name;
}

test('admin can view logs page', function () {
    $fileName = createTestLog("linha 1\nlinha 2\n");
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.logs.index'))
        ->assertOk()
        ->assertSee($fileName);
});

test('non admin users cannot view logs page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.logs.index'))
        ->assertForbidden();
});

test('admin can download log file', function () {
    $fileName = createTestLog("download\n");
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.logs.download', ['file' => $fileName]))
        ->assertOk()
        ->assertDownload($fileName);
});

test('tail endpoint returns at most last 200kb', function () {
    $marker = "\nTAIL-END\n";
    $fileName = createTestLog(str_repeat('A', 230 * 1024) . $marker);
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)
        ->get(route('admin.logs.tail', ['file' => $fileName]))
        ->assertOk();

    $content = $response->getContent();

    expect($content)->toBeString();
    expect(strlen($content))->toBeLessThanOrEqual(200 * 1024);
    expect($content)->toEndWith($marker);
});
