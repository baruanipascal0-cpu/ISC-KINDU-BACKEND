<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentBlockController extends Controller
{
    public function index(Request $request): View
    {
        $group = $request->query('group');
        $knownGroups = $this->knownGroups();
        $groups = ContentBlock::query()
            ->select('block_group')
            ->distinct()
            ->orderBy('block_group')
            ->pluck('block_group')
            ->merge(array_keys($knownGroups))
            ->unique()
            ->sort()
            ->values();

        return view('admin.content-blocks.index', [
            'blocks' => ContentBlock::query()
                ->when($group, fn ($query, string $group) => $query->where('block_group', $group))
                ->orderBy('block_group')
                ->orderBy('sort_order')
                ->paginate(20)
                ->withQueryString(),
            'group' => $group,
            'groups' => $groups,
            'knownGroups' => $knownGroups,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.content-blocks.form', [
            'block' => new ContentBlock([
                'block_group' => $request->query('group', 'home_card'),
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'knownGroups' => $this->knownGroups(),
            'metadataText' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->payload($request);
        ContentBlock::create($payload);

        return redirect()
            ->route('admin.content-blocks.index', ['group' => $payload['block_group']])
            ->with('status', 'Bloc cree.');
    }

    public function edit(ContentBlock $contentBlock): View
    {
        return view('admin.content-blocks.form', [
            'block' => $contentBlock,
            'knownGroups' => $this->knownGroups(),
            'metadataText' => $contentBlock->metadata ? json_encode($contentBlock->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
        ]);
    }

    public function update(Request $request, ContentBlock $contentBlock): RedirectResponse
    {
        $payload = $this->payload($request, $contentBlock);
        $contentBlock->update($payload);

        return redirect()
            ->route('admin.content-blocks.index', ['group' => $payload['block_group']])
            ->with('status', 'Bloc mis a jour.');
    }

    public function destroy(ContentBlock $contentBlock): RedirectResponse
    {
        $contentBlock->delete();

        return back()->with('status', 'Bloc supprime.');
    }

    private function payload(Request $request, ?ContentBlock $block = null): array
    {
        $data = $request->validate([
            'block_group' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:190', Rule::unique('content_blocks', 'key')->ignore($block?->id)],
            'title' => ['nullable', 'string', 'max:190'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'file', 'max:5120'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'link_label' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:80'],
            'metadata' => ['nullable', 'json'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imageUrl = $data['image_url'] ?? $block?->image_url;

        if ($request->hasFile('image_file')) {
            $imageUrl = '/storage/'.$request->file('image_file')->store('content/blocks', 'public');
        }

        return [
            'block_group' => $data['block_group'],
            'key' => $data['key'] ?: $this->uniqueKey($data['block_group'], $data['title'] ?? 'bloc', $block),
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'body' => $data['body'] ?? null,
            'image_url' => $imageUrl,
            'link_url' => $data['link_url'] ?? null,
            'link_label' => $data['link_label'] ?? null,
            'icon' => $data['icon'] ?? null,
            'metadata' => isset($data['metadata']) && $data['metadata'] !== '' ? json_decode($data['metadata'], true) : null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function uniqueKey(string $group, string $title, ?ContentBlock $ignore = null): string
    {
        $base = Str::slug($group).'.'.(Str::slug($title) ?: Str::lower(Str::random(8)));
        $key = $base;
        $suffix = 2;

        while (ContentBlock::query()
            ->where('key', $key)
            ->when($ignore?->exists, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->exists()) {
            $key = $base.'-'.$suffix++;
        }

        return $key;
    }

    private function knownGroups(): array
    {
        return [
            'home_slide' => 'Diaporama accueil',
            'home_card' => 'Cartes accueil',
            'home_service' => 'Services accueil',
            'admission_intro' => 'Infos inscription',
            'admission_step' => 'Etapes inscription',
            'site_footer' => 'Pied de page',
            'quick_link' => 'Liens rapides',
        ];
    }
}
