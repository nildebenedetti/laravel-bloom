@extends('layouts.categories')

@section('title', 'Edit Category')

@section('content')
<div class="container form-container p-4">
    <form action="{{ route('categories.update', $category) }}" method="POST" class="py-4" enctype="multipart/form-data">
        @csrf {{-- security token for Cross-Site Request Forgery --}}
        @method('PUT')
        <div class="row d-flex justify-content-center">
            <!-- name -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="title" class="pt-2">Name</label>
                <input required type="text" id="name" name="name" value="{{ $category->name }}">
            </div>
            <!-- description -->
            <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                <label for="description" class="py-2">Description</label>
                <textarea required id="description" name="description" rows="10" >{{ $category->description }}</textarea>
            </div>
        </div>
        <div class="btn-wrapper d-flex justify-content-end pt-4">
            <button type="submit" action class="btn btn-outline-primary px-3">Save</button>
        </div>            
    </form>
</div>
@endsection