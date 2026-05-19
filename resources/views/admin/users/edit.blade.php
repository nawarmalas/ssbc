@extends('layouts.admin')

@section('title', 'Edit Admin User - ' . $adminUser->email)
@section('page_title', 'Edit Admin User')

@section('content')
    @include('admin.users._form', [
        'adminUser' => $adminUser,
        'roles' => $roles,
        'action' => route('admin.users.update', $adminUser),
        'method' => 'PATCH',
    ])
@endsection
