@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'إضافة منتج' : 'Create Product')
@section('eyebrow', app()->isLocale('ar') ? 'إدارة المنتجات' : 'Product Management')
@section('page-title', app()->isLocale('ar') ? 'إضافة منتج جديد' : 'Create Product')

@section('content')
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.partials.product-form', [
            'submitLabel' => app()->isLocale('ar') ? 'حفظ المنتج' : 'Save Product',
        ])
    </form>
@endsection