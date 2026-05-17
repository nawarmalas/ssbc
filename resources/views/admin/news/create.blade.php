@extends('layouts.admin')

@section('title', __('admin.create') . ' — ' . __('admin.news'))

@section('content')
    <div class="w-12 h-px bg-ssbc-gold mb-4"></div>
    <h1 class="text-2xl font-display font-bold text-ssbc-green mb-8">{{ __('admin.create') }} — {{ __('admin.news') }}</h1>

    @include('admin.news._form', ['post' => $post])
@endsection
