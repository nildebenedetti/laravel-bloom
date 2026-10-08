@extends('layouts.categories')

@section('title', 'Edit Category')

@section('content')
<div class="container form-container p-4">
    <!-- Back to resource-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('categories.show', $category) }}" class="btn-lightblue">
        Back to <b>{{ $category->name }}</b>
        </a>
    </div>
    <form action="{{ route('categories.update', $category) }}" method="POST" class="py-4" enctype="multipart/form-data">
        @csrf {{-- security token for Cross-Site Request Forgery --}}
        @method('PUT')
        <div class="row d-flex justify-content-center">
            <!-- name -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="title" class="pt-2">Name</label>
                <input required maxlength="80" type="text" id="name" name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <!-- description -->
            <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                <label for="description" class="py-2">Description</label>
                <textarea required maxlength="255" id="description" name="description" rows="10" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="btn-wrapper d-flex justify-content-end pt-4">
            <button type="submit" action class="btn-lightblue">Save</button>
        </div>            
    </form>
</div>
@endsection