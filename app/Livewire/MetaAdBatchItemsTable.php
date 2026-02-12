<?php

namespace App\Livewire;

use App\Models\MetaAdBatchItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MetaAdBatchItemsTable extends Component
{
    use WithPagination;

    public int $batchId;

    public int $perPage = 10;

    public function mount(int $batchId): void
    {
        $this->batchId = $batchId;
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $items = MetaAdBatchItem::query()
            ->where('meta_ad_batch_id', $this->batchId)
            ->whereHas('batch', fn ($query) => $query->where('user_id', Auth::id()))
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.meta-ad-batch-items-table', [
            'items' => $items,
        ]);
    }
}
