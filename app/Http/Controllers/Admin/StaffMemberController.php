<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Support\PublicUpload;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffMemberController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role');

        return view('admin.staff.index', [
            'staffMembers' => StaffMember::query()
                ->when($role, fn ($query, string $role) => $query->where('role', $role))
                ->orderBy('role')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'role' => $role,
            'roles' => StaffMember::query()
                ->select('role')
                ->distinct()
                ->orderBy('role')
                ->pluck('role')
                ->filter()
                ->values(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.staff.form', [
            'staffMember' => new StaffMember([
                'role' => $request->query('role', 'enseignant'),
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'metadataText' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $staffMember = StaffMember::create($this->payload($request));

        return redirect()
            ->route('admin.staff.index', ['role' => $staffMember->role])
            ->with('status', 'Membre ajoute.');
    }

    public function edit(StaffMember $staffMember): View
    {
        return view('admin.staff.form', [
            'staffMember' => $staffMember,
            'metadataText' => $staffMember->metadata ? json_encode($staffMember->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
        ]);
    }

    public function update(Request $request, StaffMember $staffMember): RedirectResponse
    {
        $oldImage = PublicUpload::imageFrom($staffMember);

        $staffMember->update($this->payload($request, $staffMember));
        PublicUpload::deleteIfReplaced($oldImage, PublicUpload::imageFrom($staffMember));

        return redirect()
            ->route('admin.staff.index', ['role' => $staffMember->role])
            ->with('status', 'Membre mis a jour.');
    }

    public function destroy(StaffMember $staffMember): RedirectResponse
    {
        $image = PublicUpload::imageFrom($staffMember);
        $staffMember->delete();
        PublicUpload::delete($image['image_disk'], $image['image_public_id']);

        return back()->with('status', 'Membre supprime.');
    }

    private function payload(Request $request, ?StaffMember $staffMember = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'title' => ['nullable', 'string', 'max:190'],
            'role' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:80'],
            'biography' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:190'],
            'metadata' => ['nullable', 'json'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $image = $this->imagePayload($request, $data, $staffMember);

        return [
            'name' => $data['name'],
            'slug' => $this->slug($data, $staffMember),
            'title' => $data['title'] ?? null,
            'role' => $data['role'],
            'department' => $data['department'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'biography' => $data['biography'] ?? null,
            'image_url' => $image['image_url'],
            'image_public_id' => $image['image_public_id'],
            'image_disk' => $image['image_disk'],
            'image_alt' => $image['image_alt'],
            'metadata' => isset($data['metadata']) && $data['metadata'] !== '' ? json_decode($data['metadata'], true) : null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function slug(array $data, ?StaffMember $staffMember): string
    {
        $input = trim((string) ($data['slug'] ?? ''));

        if ($input !== '') {
            return UniqueSlug::forModel(StaffMember::class, $input, $staffMember);
        }

        if ($staffMember?->exists && $staffMember->slug) {
            return $staffMember->slug;
        }

        return UniqueSlug::forModel(StaffMember::class, $data['name'], $staffMember);
    }

    private function imagePayload(Request $request, array $data, ?StaffMember $staffMember): array
    {
        $alt = $data['image_alt'] ?? $data['name'] ?? null;

        if ($request->hasFile('image_file')) {
            return PublicUpload::storeImage($request->file('image_file'), 'content/staff', $alt);
        }

        if (! empty($data['image_url'])) {
            return PublicUpload::externalImage($data['image_url'], $alt);
        }

        $current = PublicUpload::imageFrom($staffMember);
        $current['image_alt'] = $alt ?: ($current['image_alt'] ?? null);

        return $current;
    }
}
