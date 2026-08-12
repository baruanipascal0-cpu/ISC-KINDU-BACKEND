<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'groups' => SiteSetting::query()
                ->orderBy('group')
                ->orderBy('key')
                ->get()
                ->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        foreach ($data['settings'] ?? [] as $key => $value) {
            $setting = SiteSetting::where('key', $key)->first();

            if (! $setting) {
                continue;
            }

            $setting->update([
                'value' => $this->normalizedValue($setting, $value),
            ]);
        }

        SiteSetting::query()
            ->where('type', 'boolean')
            ->whereNotIn('key', array_keys($data['settings'] ?? []))
            ->get()
            ->each(fn (SiteSetting $setting) => $setting->update(['value' => false]));

        return back()->with('status', 'Parametres mis a jour.');
    }

    private function normalizedValue(SiteSetting $setting, mixed $value): mixed
    {
        if ($setting->type === 'boolean') {
            return (bool) $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item): bool => $item !== null && $item !== ''));
        }

        return $value;
    }
}
