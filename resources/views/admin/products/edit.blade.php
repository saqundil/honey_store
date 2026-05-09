@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'تعديل المنتج' : 'Edit Product')
@section('eyebrow', app()->isLocale('ar') ? 'إدارة المنتجات' : 'Product Management')
@section('page-title', app()->isLocale('ar') ? 'تعديل المنتج' : 'Edit Product')

@section('content')
    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.partials.product-form', [
            'submitLabel' => app()->isLocale('ar') ? 'حفظ التعديلات' : 'Save Changes',
        ])
    </form>
@endsection