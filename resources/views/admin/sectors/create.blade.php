@extends('layouts.admin')

@section('title', 'Add Sector - ' . __('admin.title'))
@section('page_title', 'Add Sector')

@section('content')
    @include('admin.sectors._form', ['sector' => $sector])
@endsection
