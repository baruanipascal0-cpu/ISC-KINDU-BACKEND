<?php

namespace App\Http\Controllers\Api;

use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\GraduationList;
use App\Models\MenuItem;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\Publication;
use App\Models\Section;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentController extends ApiController
{
    public function settings(): JsonResponse
    {
        $settings = SiteSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value]);

        return $this->ok($settings);
    }

    public function menus(): JsonResponse
    {
        $menus = MenuItem::query()
            ->where('is_active', true)
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('location')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('location')
            ->map(fn ($items) => $items->values());

        return $this->ok($menus);
    }

    public function media(): JsonResponse
    {
        return $this->ok([
            'logo' => $this->mediaUrl('/images/site/logo.jpg'),
            'photos' => collect(range(1, 9))
                ->map(fn (int $number) => $this->mediaUrl("/images/site/photo-{$number}.jpg"))
                ->all(),
        ]);
    }

    public function blocks(Request $request, ?string $group = null): JsonResponse
    {
        $group ??= $request->query('group');

        $blocks = ContentBlock::query()
            ->where('is_active', true)
            ->when($group, fn ($query, string $group) => $query->where('block_group', $group))
            ->orderBy('block_group')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ContentBlock $block) => $this->blockResource($block));

        return $this->ok($blocks);
    }

    public function homeSlides(): JsonResponse
    {
        $blocks = ContentBlock::query()
            ->where('block_group', 'home_slide')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($blocks->isNotEmpty()) {
            return $this->ok($blocks->map(fn (ContentBlock $block) => [
                'title' => $block->title,
                'subtitle' => $block->subtitle ?: $block->body,
                'image_url' => $this->mediaUrl($block->image_url),
                'link_url' => $block->link_url ?: '/',
                'link_label' => $block->link_label,
            ]));
        }

        $slides = NewsPost::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(3)
            ->get()
            ->map(fn (NewsPost $post) => [
                'title' => $post->title,
                'subtitle' => $post->excerpt,
                'image_url' => $this->mediaUrl($post->image_url),
                'link_url' => '/actualites/'.$post->slug,
            ]);

        return $this->ok($slides);
    }

    public function homeCards(): JsonResponse
    {
        $blocks = ContentBlock::query()
            ->where('block_group', 'home_card')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($blocks->isNotEmpty()) {
            return $this->ok($blocks->map(fn (ContentBlock $block) => [
                'title' => $block->title,
                'summary' => $block->subtitle ?: Str::limit(strip_tags($block->body ?? ''), 160),
                'url' => $block->link_url ?: '#',
                'image_url' => $this->mediaUrl($block->image_url),
                'link_label' => $block->link_label,
                'icon' => $block->icon,
            ]));
        }

        return $this->ok([]);
    }

    public function homeStatistics(): JsonResponse
    {
        return $this->ok([
            'sections' => Section::where('is_active', true)->count(),
            'programs' => Program::where('is_active', true)->count(),
            'news' => NewsPost::where('is_published', true)->count(),
            'publications' => Publication::where('is_published', true)->count(),
        ]);
    }

    public function pages(): JsonResponse
    {
        $pages = Page::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get()
            ->map(fn (Page $page) => $this->withMedia($page->toArray()));

        return $this->ok($pages);
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->ok($this->withMedia($page->toArray()));
    }

    public function sections(): JsonResponse
    {
        $sections = Section::query()
            ->with(['programs' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->ok($sections);
    }

    public function section(string $slug): JsonResponse
    {
        $section = Section::query()
            ->with(['programs' => fn ($query) => $query->where('is_active', true)])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->ok($section);
    }

    public function programs(Request $request): JsonResponse
    {
        $programs = Program::query()
            ->with('section')
            ->where('is_active', true)
            ->when($request->query('section'), function ($query, string $section) {
                $query->whereHas('section', fn ($sectionQuery) => $sectionQuery
                    ->where('slug', $section)
                    ->orWhere('name', $section));
            })
            ->orderBy('name')
            ->get();

        return $this->ok($programs);
    }

    public function news(Request $request): JsonResponse
    {
        $posts = NewsPost::query()
            ->where('is_published', true)
            ->when($request->query('category'), fn ($query, string $category) => $query->where('category', $category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->through(fn (NewsPost $post) => $this->withMedia($post->toArray()));

        return $this->ok($posts->items(), meta: $this->paginationMeta($posts));
    }

    public function newsCategories(): JsonResponse
    {
        return $this->ok(
            NewsPost::query()
                ->where('is_published', true)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
        );
    }

    public function newsShow(string $slug): JsonResponse
    {
        $post = NewsPost::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->ok($this->withMedia($post->toArray()));
    }

    public function publications(Request $request): JsonResponse
    {
        $items = Publication::query()
            ->where('is_published', true)
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->through(fn (Publication $publication) => $this->withMedia($publication->toArray()));

        return $this->ok($items->items(), meta: $this->paginationMeta($items));
    }

    public function publicationShow(string $slug): JsonResponse
    {
        $publication = Publication::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->ok($this->withMedia($publication->toArray()));
    }

    public function fees(Request $request): JsonResponse
    {
        $items = Publication::query()
            ->where('type', 'Frais')
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->through(fn (Publication $fee) => $this->withMedia($fee->toArray()));

        return $this->ok($items->items(), meta: $this->paginationMeta($items));
    }

    public function feeShow(string $slug): JsonResponse
    {
        $fee = Publication::query()
            ->where('slug', $slug)
            ->where('type', 'Frais')
            ->where('is_published', true)
            ->firstOrFail();

        return $this->ok($this->withMedia($fee->toArray()));
    }

    public function graduationLists(Request $request): JsonResponse
    {
        $items = GraduationList::query()
            ->with(['academicYear', 'section', 'program', 'promotion'])
            ->withCount('graduates')
            ->where('status', 'published')
            ->when($request->query('academic_year_id'), fn ($query, string $id) => $query->where('academic_year_id', $id))
            ->when($request->query('section_id'), fn ($query, string $id) => $query->where('section_id', $id))
            ->when($request->query('program_id'), fn ($query, string $id) => $query->where('program_id', $id))
            ->when($request->query('promotion_id'), fn ($query, string $id) => $query->where('promotion_id', $id))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->through(fn (GraduationList $list) => $this->graduationListResource($list));

        return $this->ok($items->items(), meta: $this->paginationMeta($items));
    }

    public function graduationListShow(string $slug): JsonResponse
    {
        $list = GraduationList::query()
            ->with([
                'academicYear',
                'section',
                'program',
                'promotion',
                'graduates' => fn ($query) => $query->orderBy('sort_order')->orderBy('last_name'),
            ])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return $this->ok($this->graduationListResource($list, includeGraduates: true));
    }

    public function events(Request $request): JsonResponse
    {
        $items = Event::query()
            ->where('is_published', true)
            ->when($request->boolean('upcoming'), fn ($query) => $query->where('starts_at', '>=', now()))
            ->when(
                $request->boolean('upcoming'),
                fn ($query) => $query->orderBy('starts_at')->orderByDesc('id'),
                fn ($query) => $query->orderByDesc('starts_at')->orderByDesc('id')
            )
            ->paginate($this->perPage($request))
            ->through(fn (Event $event) => $this->withMedia($event->toArray()));

        return $this->ok($items->items(), meta: $this->paginationMeta($items));
    }

    public function eventShow(string $slug): JsonResponse
    {
        $event = Event::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->ok($this->withMedia($event->toArray()));
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 10), 1), 50);
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function withMedia(array $payload): array
    {
        foreach (['image_url', 'file_url'] as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = $this->mediaUrl($payload[$field]);
            }
        }

        return $payload;
    }

    private function blockResource(ContentBlock $block): array
    {
        return [
            'id' => $block->id,
            'key' => $block->key,
            'group' => $block->block_group,
            'title' => $block->title,
            'subtitle' => $block->subtitle,
            'summary' => $block->subtitle ?: Str::limit(strip_tags($block->body ?? ''), 160),
            'body' => $block->body,
            'image_url' => $this->mediaUrl($block->image_url),
            'link_url' => $block->link_url,
            'link_label' => $block->link_label,
            'icon' => $block->icon,
            'metadata' => $block->metadata ?? [],
            'sort_order' => $block->sort_order,
        ];
    }

    private function graduationListResource(GraduationList $list, bool $includeGraduates = false): array
    {
        $payload = [
            'id' => $list->id,
            'title' => $list->title,
            'slug' => $list->slug,
            'cycle' => $list->cycle,
            'status' => $list->status,
            'decision_date' => $list->decision_date?->toDateString(),
            'published_at' => $list->published_at?->toIso8601String(),
            'academic_year' => $list->academicYear,
            'section' => $list->section,
            'program' => $list->program,
            'promotion' => $list->promotion,
            'graduates_count' => $list->graduates_count ?? $list->graduates()->count(),
        ];

        if ($includeGraduates) {
            $payload['graduates'] = $list->graduates->map(fn ($graduate) => [
                'id' => $graduate->id,
                'matricule' => $graduate->matricule,
                'last_name' => $graduate->last_name,
                'post_name' => $graduate->post_name,
                'first_name' => $graduate->first_name,
                'gender' => $graduate->gender,
                'percentage' => $graduate->percentage,
                'mention' => $graduate->mention,
            ])->values();
        }

        return $payload;
    }

    private function mediaUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
