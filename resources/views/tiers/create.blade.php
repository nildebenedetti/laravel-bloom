@extends('layouts.tiers')

@section('title', 'Add a New Tier')

@section('content')

<div class="container form-container p-4">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('tiers.index') }}" class="btn-lightblue">
        Back to All
        </a>
    </div>
    <!--white bg wrapper-->
        <div class="container bg-white bg-opacity-75 rounded-2 my-3">
        <form action="{{ route('tiers.store') }}" method="POST" class="py-4" enctype="multipart/form-data">
            @csrf {{-- security token for Cross-Site Request Forgery --}}
            <div class="row d-flex justify-content-center">
                <!-- name -->
                <div class="col col-sm-12 d-flex flex-column">
                    <label for="title" class="pt-2">Name</label>
                    <input required maxlength="80" type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- description -->
                <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                    <label for="description" class="py-2">Description <small class="text-muted">(optional)</small></label>
                    <textarea maxlength="255" id="description" name="description" rows="10" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
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
</div>
@endsection