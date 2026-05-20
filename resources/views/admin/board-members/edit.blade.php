@extends('layouts.admin')

@section('title', 'Edit Board Member — ' . __('admin.title'))
@section('page_title', 'Edit: ' . $member->name_en)

@section('content')
    @include('admin.board-members._form', ['member' => $member])
@endsection
