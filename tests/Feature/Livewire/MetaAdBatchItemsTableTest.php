<?php

use App\Livewire\MetaAdBatchItemsTable;
use App\Models\MetaAdBatch;
use App\Models\MetaAdBatchItem;
use App\Models\User;
use Livewire\Livewire;

function createBatchForUser(User $user): MetaAdBatch
{
    return MetaAdBatch::query()->create([
        'user_id' => $user->id,
        'objective' => 'OUTCOME_AWARENESS',
        'destination_type' => 'WEBSITE',
        'ad_account_id' => 'act_123456',
        'url_template' => 'https://example.com',
        'title_template' => 'Titulo',
        'body_template' => 'Texto',
        'status' => 'queued',
    ]);
}

test('component lists only items from the authenticated user batch', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownerBatch = createBatchForUser($owner);
    $otherBatch = createBatchForUser($otherUser);

    MetaAdBatchItem::query()->create([
        'meta_ad_batch_id' => $ownerBatch->id,
        'city_name' => 'Goiania',
        'status' => 'success',
        'error_message' => null,
    ]);

    MetaAdBatchItem::query()->create([
        'meta_ad_batch_id' => $otherBatch->id,
        'city_name' => 'Sao Paulo',
        'status' => 'error',
        'error_message' => 'Falha',
    ]);

    $this->actingAs($owner);

    Livewire::test(MetaAdBatchItemsTable::class, ['batchId' => $ownerBatch->id])
        ->assertSee('Goiania')
        ->assertDontSee('Sao Paulo');
});

test('component paginates batch items', function () {
    $user = User::factory()->create();
    $batch = createBatchForUser($user);

    foreach (range(1, 12) as $index) {
        MetaAdBatchItem::query()->create([
            'meta_ad_batch_id' => $batch->id,
            'city_name' => sprintf('Cidade-%03d', $index),
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    $this->actingAs($user);

    Livewire::test(MetaAdBatchItemsTable::class, ['batchId' => $batch->id])
        ->assertSee('Cidade-012')
        ->assertDontSee('Cidade-001')
        ->call('gotoPage', 2)
        ->assertSee('Cidade-001');
});
