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
                'value' => $setting->type === 'boolean' ? (bool) $value : $value,
            ]);
        }

        SiteSetting::query()
            ->where('type', 'boolean')
            ->whereNotIn('key', array_keys($data['settings'] ?? []))
            ->update(['value' => false]);

        return back()->with('status', 'Parametres mis a jour.');
    }
}
