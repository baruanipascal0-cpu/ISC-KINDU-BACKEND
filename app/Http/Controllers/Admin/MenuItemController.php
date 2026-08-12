<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function index(Request $request): View
    {
        $location = $request->query('location');
        $locations = MenuItem::query()
            ->select('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return view('admin.menu-items.index', [
            'items' => MenuItem::query()
                ->with('parent')
                ->when($location, fn ($query, string $location) => $query->where('location', $location))
                ->orderBy('location')
                ->orderBy('parent_id')
                ->orderBy('sort_order')
                ->paginate(30)
                ->withQueryString(),
            'location' => $location,
            'locations' => $locations,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.menu-items.form', [
            'item' => new MenuItem([
                'location' => $request->query('location', 'main'),
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'parents' => $this->parents(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = MenuItem::create($this->payload($request));

        return redirect()
            ->route('admin.menu-items.index', ['location' => $item->location])
            ->with('status', 'Lien de menu ajoute.');
    }

    public function edit(MenuItem $menuItem): View
    {
        return view('admin.menu-items.form', [
            'item' => $menuItem,
            'parents' => $this->parents($menuItem),
        ]);
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update($this->payload($request, $menuItem));

        return redirect()
            ->route('admin.menu-items.index', ['location' => $menuItem->location])
            ->with('status', 'Lien de menu mis a jour.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return back()->with('status', 'Lien de menu supprime.');
    }

    private function payload(Request $request, ?MenuItem $menuItem = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('menu_items', 'id')],
            'location' => ['required', 'string', 'max:80'],
            'label' => ['required', 'string', 'max:190'],
            'url' => ['required', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($menuItem?->exists && (int) ($data['parent_id'] ?? 0) === $menuItem->id) {
            $data['parent_id'] = null;
        }

        return [
            'parent_id' => $data['parent_id'] ?? null,
            'location' => $data['location'],
            'label' => $data['label'],
            'url' => $data['url'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function parents(?MenuItem $ignore = null)
    {
        return MenuItem::query()
            ->when($ignore?->exists, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->orderBy('location')
            ->orderBy('sort_order')
            ->get();
    }
}
