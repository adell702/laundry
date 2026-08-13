@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<div class="max-w-2xl space-y-6">
    <x-card class="p-6">
        @include('profile.partials.update-profile-information-form')
    </x-card>
    <x-card class="p-6">
        @include('profile.partials.update-password-form')
    </x-card>
</div>
@endsection
