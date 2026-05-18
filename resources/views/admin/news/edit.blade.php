@extends('layouts.admin')

@section('title', __('admin.edit') . ' — ' . $post->title_en)
@section('page_title', __('admin.edit') . ' — ' . $post->title_en)

@section('content')
    @include('admin.news._form', ['post' => $post])
@endsection
