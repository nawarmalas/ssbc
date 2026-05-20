@extends('layouts.admin')

@section('title', 'Add Board Member — ' . __('admin.title'))
@section('page_title', 'Add Board Member')

@section('content')
    @include('admin.board-members._form', ['member' => $member])
@endsection
