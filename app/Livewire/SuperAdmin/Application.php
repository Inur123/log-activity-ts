<?php
// app/Livewire/SuperAdmin/Application.php

namespace App\Livewire\SuperAdmin;

use App\Models\Application as ApplicationModel;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.super-admin')]
#[Title('Applications')]
class Application extends Component
{
    public string $action = 'index'; // index | form | detail

    public ?string $appId = null;
    public ?ApplicationModel $selected = null;

    // filters
    public string $q = '';
    public string $stack = '';
    public string $active = ''; // '' | '1' | '0'
    public int $per_page = 25;
    public string $sort = 'newest'; // newest|oldest
    public int $page = 1;

    // form fields
    public string $name = '';
    public string $domain = '';
    public string $form_stack = 'other';
    public bool $is_active = true;

    // api key preview (pending) - only on edit
    public ?string $pending_api_key = null;
    public ?string $original_api_key = null;

    public function gotoPage(int $p, int $lastPage): void
    {
        $this->page = max(1, min($p, $lastPage));
    }

    public function nextPage(int $lastPage): void
    {
        if ($this->page < $lastPage) $this->page++;
    }

    public function prevPage(): void
    {
        if ($this->page > 1) $this->page--;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->action = 'form';
        $this->dispatch('scroll-top');
    }

    public function edit(string $id): void
    {
        $app = ApplicationModel::findOrFail($id);

        $this->appId = $app->id;
        $this->selected = $app;

        $this->name = $app->name;
        $this->domain = $app->domain ?? '';
        $this->form_stack = $app->stack ?? 'other';
        $this->is_active = (bool) $app->is_active;

        $this->original_api_key = $app->api_key;
        $this->pending_api_key = null;

        $this->action = 'form';
        $this->dispatch('scroll-top');
    }

    public function detail(string $id): void
    {
        $app = ApplicationModel::findOrFail($id);

        $this->appId = $app->id;
        $this->selected = $app;

        $this->pending_api_key = null;
        $this->original_api_key = $app->api_key;

        $this->action = 'detail';
        $this->dispatch('scroll-top');
    }

    public function back(): void
    {
        $this->action = 'index';
        $this->resetForm();
        $this->dispatch('scroll-top');
    }

    /**
     * Regenerate hanya preview (tidak simpan DB)
     * Baru tersimpan kalau klik Save.
     */
    public function regenerateApiKeyPreview(): void
    {
        if (!$this->appId) return;

        $this->pending_api_key = ApplicationModel::generateApiKey();

        // OPTIONAL: supaya tampilan langsung berubah meskipun view mengacu ke selected->api_key
        if ($this->selected) {
            $this->selected->api_key = $this->pending_api_key;
        }

       $this->dispatch('flash', type: 'warning', message: 'API Key baru dibuat (preview). Klik Save untuk menyimpan.');
    }

    public function save(): void
    {
        $slug = Str::slug($this->name);

        $this->validate(
        [
            'name'       => ['required', 'string', 'max:100'],
            'domain'     => ['nullable', 'string', 'max:255'],
            'form_stack' => ['required', 'in:laravel,codeigniter,django,other'],
            'is_active'  => ['boolean'],
        ],
        [
            'name.required'       => 'Name wajib diisi.',
            'name.max'            => 'Name maksimal 100 karakter.',
            'form_stack.required' => 'Stack wajib dipilih.',
            'form_stack.in'       => 'Stack tidak valid.',
            'domain.max'          => 'Domain maksimal 255 karakter.',
        ]
    );

        // slug unik
        $baseSlug = $slug;
        $i = 1;

        $exists = function (string $s): bool {
            $q = ApplicationModel::where('slug', $s);
            if ($this->appId) $q->where('id', '!=', $this->appId);
            return $q->exists();
        };

        while ($exists($slug)) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }

        if ($this->appId) {
            $app = ApplicationModel::findOrFail($this->appId);

            $payload = [
                'name' => $this->name,
                'slug' => $slug,
                'domain' => $this->domain ?: null,
                'stack' => $this->form_stack,
                'is_active' => $this->is_active,
            ];

            // kalau ada preview token baru → simpan token baru
            if (!empty($this->pending_api_key)) {
                $payload['api_key'] = $this->pending_api_key;
            }

            $app->update($payload);

            // refresh selected supaya UI konsisten
            $this->selected = $app->fresh();
            $this->original_api_key = $this->selected->api_key;
            $this->pending_api_key = null;
        } else {
            ApplicationModel::create([
                'name' => $this->name,
                'slug' => $slug,
                'domain' => $this->domain ?: null,
                'stack' => $this->form_stack,
                'is_active' => $this->is_active,
                // api_key auto generate di model
            ]);
        }

        $this->action = 'index';
        $this->resetForm();
        $this->dispatch('scroll-top');

       $this->dispatch('flash', type: 'success', message: 'Application saved.');

    }

    public function delete(string $id): void
    {
        $app = ApplicationModel::findOrFail($id);
        $app->delete();

        $this->dispatch('flash', type: 'success', message: 'Application deleted.');
        $this->dispatch('scroll-top');
    }

    private function resetForm(): void
    {
        $this->appId = null;
        $this->selected = null;

        $this->name = '';
        $this->domain = '';
        $this->form_stack = 'other';
        $this->is_active = true;

        $this->pending_api_key = null;
        $this->original_api_key = null;
    }

    private function buildQuery()
    {
        $q = ApplicationModel::query();

        $this->sort === 'oldest'
            ? $q->oldest('created_at')
            : $q->latest('created_at');

        if ($this->stack !== '') $q->where('stack', $this->stack);
        if ($this->active !== '') $q->where('is_active', (bool) (int) $this->active);

        if (trim($this->q) !== '') {
            $term = trim($this->q);
            $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('domain', 'like', "%{$term}%")
                    ->orWhere('api_key', 'like', "%{$term}%");
            });
        }

        return $q;
    }

    public function getFiltered()
    {
        $base = $this->buildQuery();

        $total = (clone $base)->count();
        $perPage = $this->per_page;

        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($this->page > $lastPage) $this->page = $lastPage;

        $items = $base->forPage($this->page, $perPage)->get();

        return [$items, $total, $lastPage];
    }

    public function render()
    {
        [$items, $total, $lastPage] = $this->getFiltered();

        return match ($this->action) {
            'form' => view('livewire.super-admin.application.form', [
                'selected' => $this->selected,
                'displayApiKey' => $this->pending_api_key ?: ($this->selected?->api_key ?? ''),
                'isPreviewKey' => !empty($this->pending_api_key),
            ]),

            'detail' => view('livewire.super-admin.application.detail', [
                'app' => $this->selected,
            ]),

            default => view('livewire.super-admin.application.index', [
                'apps' => $items,
                'total' => $total,
                'lastPage' => $lastPage,
                'page' => $this->page,
                'per_page' => $this->per_page,
            ]),
        };
    }
}
