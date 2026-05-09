@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'إضافة بائع' : 'Create Seller')
@section('eyebrow', app()->isLocale('ar') ? 'إدارة البائعين' : 'Sellers Management')
@section('page-title', app()->isLocale('ar') ? 'إضافة بائع جديد' : 'Create Seller')

@section('content')
    <form method="POST" action="{{ route('admin.sellers.store') }}">
        @csrf
        @include('admin.partials.seller-form', [
            'submitLabel' => app()->isLocale('ar') ? 'حفظ البائع' : 'Save Seller',
        ])
    </form>
@endsection