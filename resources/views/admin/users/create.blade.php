@extends('layouts.admin')

@section('title', 'Create Admin User - ' . __('admin.title'))
@section('page_title', 'Create Admin User')

@section('content')
    @include('admin.users._form', [
        'adminUser' => $adminUser,
        'roles' => $roles,
        'action' => route('admin.users.store'),
        'method' => 'POST',
    ])
@endsection
