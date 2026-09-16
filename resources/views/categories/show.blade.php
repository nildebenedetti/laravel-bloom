@extends('layouts.categories')

@section('title', 'All Categories')

@section('content')
<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('categories.index') }}" class="btn bg-light-blue text-secondary">
        Back to All
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper px-5 d-flex justify-content-end align-items-center">
        <a href="#" class="btn btn-outline-success">
        Add New
        </a>
    </div>
</div>
<div class="page-title-wrapper container pt-4 text-capitalize d-flex flex-column">
    <h4 class="page-title text-secondary">
        {{ $category->name }}
    </h4>
</div>
<!-- Description -->
<div class="container text-justify">
    <p>{{ $category->description }}</p>
</div>

@endsection