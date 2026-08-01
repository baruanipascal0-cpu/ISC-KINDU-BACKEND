@extends('admin.layout')

@section('title', 'Parametres du site')
@section('subtitle', 'Nom, contacts, logo et ouverture des inscriptions')

@section('content')
    <form class="card" method="post" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $settings)
            <div class="card-header">
                <h2 class="card-title">{{ ucfirst($group) }}</h2>
            </div>
            <div class="card-body form-grid">
                @foreach ($settings as $setting)
                    <div class="field {{ $setting->type === 'textarea' ? 'full' : '' }}">
                        <label for="setting-{{ $setting->id }}">{{ $setting->key }}</label>
                        @if ($setting->type === 'boolean')
                            <label class="checkbox-row">
                                <input id="setting-{{ $setting->id }}" type="checkbox" name="settings[{{ $setting->key }}]" value="1" @checked((bool) $setting->value)>
                                Actif
                            </label>
                        @elseif ($setting->type === 'textarea')
                            <textarea id="setting-{{ $setting->id }}" name="settings[{{ $setting->key }}]">{{ old('settings.'.$setting->key, $setting->value) }}</textarea>
                        @else
                            <input id="setting-{{ $setting->id }}" name="settings[{{ $setting->key }}]" value="{{ old('settings.'.$setting->key, $setting->value) }}">
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
