@extends('admin.layout')

@section('title', 'Parametres du site')
@section('subtitle', 'Nom, contacts, logo et ouverture des inscriptions')

@section('content')
    @php
        $groupLabels = [
            'general' => 'Informations institutionnelles',
            'admissions' => 'Inscriptions',
            'reseaux_sociaux' => 'Reseaux sociaux',
        ];

        $settingLabels = [
            'institution.name' => 'Nom complet',
            'institution.short_name' => 'Nom court',
            'institution.code' => 'Code institution',
            'institution.logo_url' => 'Logo',
            'institution.email' => 'E-mail public',
            'institution.phone' => 'Telephone public',
            'institution.address' => 'Adresse publique',
            'admissions.is_open' => 'Inscriptions ouvertes',
            'admissions.academic_year' => 'Annee academique',
            'social.facebook_url' => 'Facebook',
            'social.x_url' => 'X / Twitter',
            'social.linkedin_url' => 'LinkedIn',
            'social.youtube_url' => 'YouTube',
            'social.email' => 'E-mail reseaux/contact',
        ];
    @endphp

    <form class="card" method="post" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $settings)
            <div class="card-header">
                <h2 class="card-title">{{ $groupLabels[$group] ?? ucfirst($group) }}</h2>
            </div>
            <div class="card-body form-grid">
                @foreach ($settings as $setting)
                    @php
                        $oldSettings = old('settings', []);
                        $fieldValue = is_array($oldSettings) && array_key_exists($setting->key, $oldSettings)
                            ? $oldSettings[$setting->key]
                            : $setting->value;

                        if (is_array($fieldValue)) {
                            $fieldValue = implode(', ', array_filter($fieldValue, fn ($item) => $item !== null && $item !== ''));
                        }

                        $fieldValue = $fieldValue ?? '';
                    @endphp
                    <div class="field {{ $setting->type === 'textarea' ? 'full' : '' }}">
                        <label for="setting-{{ $setting->id }}">{{ $settingLabels[$setting->key] ?? $setting->key }}</label>
                        <span class="muted">{{ $setting->key }}</span>
                        @if ($setting->type === 'boolean')
                            <label class="checkbox-row">
                                <input id="setting-{{ $setting->id }}" type="checkbox" name="settings[{{ $setting->key }}]" value="1" @checked((bool) $fieldValue)>
                                Actif
                            </label>
                        @elseif ($setting->type === 'textarea')
                            <textarea id="setting-{{ $setting->id }}" name="settings[{{ $setting->key }}]">{{ $fieldValue }}</textarea>
                        @else
                            <input id="setting-{{ $setting->id }}" name="settings[{{ $setting->key }}]" value="{{ $fieldValue }}">
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="card-header">
            <span class="muted">Ces valeurs alimentent le site public et l inscription.</span>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
