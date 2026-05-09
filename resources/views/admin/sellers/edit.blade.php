@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'تعديل البائع' : 'Edit Seller')
@section('eyebrow', app()->isLocale('ar') ? 'إدارة البائعين' : 'Sellers Management')
@section('page-title', app()->isLocale('ar') ? 'تعديل بيانات البائع' : 'Edit Seller')

@section('content')
    <form method="POST" action="{{ route('admin.sellers.update', $seller) }}">
        @csrf
        @method('PUT')
        @include('admin.partials.seller-form', [
            'submitLabel' => app()->isLocale('ar') ? 'حفظ التعديلات' : 'Save Changes',
        ])
    </form>
@endsection