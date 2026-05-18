@extends('layouts.admin')

@section('title', __('admin.create') . ' — ' . __('admin.news'))
@section('page_title', __('admin.create') . ' — ' . __('admin.news'))

@section('content')
    @include('admin.news._form', ['post' => $post])
@endsection
