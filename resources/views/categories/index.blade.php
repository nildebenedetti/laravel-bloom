@extends('layouts.categories')

@section('title', 'All Categories')

@section('content')
    <!-- Back to All btn-->
    <div class="btn-wrapper px-5 d-flex justify-content-start">
        <a href="{{ route('dashboard') }}" class="btn bg-light-blue mt-3 text-dark">
            Back to Dashboard
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper px-5 d-flex justify-content-end">
        <a href="#" class="btn bg-success mt-3 text-light">
            Add New
        </a>
    </div>
    <div class="container py-5">
        <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3 g-3">
            @foreach($categories as $category)
            <p>{{ $category->name }}</p>
            @endforeach
        </div>
    </div>
@endsection