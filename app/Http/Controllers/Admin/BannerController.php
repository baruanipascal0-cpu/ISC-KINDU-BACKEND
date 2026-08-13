<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerController extends Controller
{
    private const GROUP = 'home_slide';

    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => ContentBlock::query()
                ->where('block_group', self::GROUP)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.form', [
            'banner' => new ContentBlock([
                'block_group' => self::GROUP,
                'is_active' => true,
                'sort_order' => 0,
                'link_label' => 'Lire la suite',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ContentBlock::create($this->payload($request));

        return redirect()
            ->route('admin.banners.index')
            ->with('status', 'Banniere creee.');
    }

    public function edit(ContentBlock $banner): View
    {
        $this->ensureBanner($banner);

        return view('admin.banners.form', [
            'banner' => $banner,
        ]);
    }

    public function update(Request $request, ContentBlock $banner): RedirectResponse
    {
        $this->ensureBanner($banner);

        $oldImage = PublicUpload::imageFrom($banner);
        $banner->update($this->payload($request, $banner));
        PublicUpload::deleteIfReplaced($oldImage, PublicUpload::imageFrom($banner));

        return redirect()
            ->route('admin.banners.index')
            ->with('status', 'Banniere mise a jour.');
    }

    public function destroy(ContentBlock $banner): RedirectResponse
    {
        $this->ensureBanner($banner);

        $image = PublicUpload::imageFrom($banner);
        $banner->delete();
        PublicUpload::delete($image['image_disk'], $image['image_public_id']);

        return back()->with('status', 'Banniere supprimee.');
    }

    private function payload(Request $request, ?ContentBlock $banner = null): array
    {
        $data = $request->validate([
            'key' => ['nullable', 'string', 'max:190', Rule::unique('content_blocks', 'key')->ignore($banner?->id)],
            'title' => ['required', 'string', 'max:190'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:190'],
            'clear_image' => ['nullable', 'boolean'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'link_label' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $image = $this->imagePayload($request, $data, $banner);

        return [
            'block_group' => self::GROUP,
            'key' => ($data['key'] ?? null) ?: $this->uniqueKey($data['title'], $banner),
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'body' => $data['body'] ?? null,
            'image_url' => $image['image_url'],
            'image_public_id' => $image['image_public_id'],
            'image_disk' => $image['image_disk'],
            'image_alt' => $image['image_alt'],
            'link_url' => $data['link_url'] ?? null,
            'link_label' => $data['link_label'] ?? null,
            'icon' => null,
            'metadata' => null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function imagePayload(Request $request, array $data, ?ContentBlock $banner): array
    {
        $alt = $data['image_alt'] ?? $data['title'] ?? null;

        if ($request->boolean('clear_image')) {
            return PublicUpload::externalImage(null, $alt);
        }

        if ($request->hasFile('image_file')) {
            return PublicUpload::storeImage($request->file('image_file'), 'content/banners', $alt);
        }

        if (! empty($data['image_url'])) {
            return PublicUpload::externalImage($data['image_url'], $alt);
        }

        $current = PublicUpload::imageFrom($banner);
        $current['image_alt'] = $alt ?: ($current['image_alt'] ?? null);

        return $current;
    }

    private function uniqueKey(string $title, ?ContentBlock $ignore = null): string
    {
        $base = self::GROUP.'.'.(Str::slug($title) ?: Str::lower(Str::random(8)));
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

    private function ensureBanner(ContentBlock $banner): void
    {
        abort_unless($banner->block_group === self::GROUP, 404);
    }
}
