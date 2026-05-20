@extends('layouts.admin')

@section('title', 'Edit Sector - ' . __('admin.title'))
@section('page_title', 'Edit: ' . $sector->name_en)

@section('content')
    @include('admin.sectors._form', ['sector' => $sector])
@endsection
