@extends('layouts.emotions')

@section('title', 'Edit Emotion')

@section('content')

<!-- Header Action Buttons -->
<div class="container d-flex justify-content-between mt-3">
        <!-- Back to resource-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('emotions.show', $emotion) }}" class="btn-lightblue">
        <small>Back to: <b>{{ $emotion->name }}</b></small>
        </a>
    </div>
</div>

<!-- White Mask Card Container -->
<div class="container bg-white bg-opacity-75 rounded-3 border shadow-sm my-3 p-4">

    <form action="{{ route('emotions.update', $emotion) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Name -->
            <div class="col-12 col-lg-6 d-flex flex-column">
                <label for="name" class="form-label fw-semibold text-secondary">Name</label>
                <input type="text" 
                    id="name" 
                    name="name" 
                    class="form-control @error('name') is-invalid @enderror" 
                    value="{{ old('name', $emotion->name) }}" 
                    required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Color -->
            <div class="col-12 col-lg-6 d-flex flex-column">
                <label for="color" class="form-label fw-semibold text-secondary">Color</label>
                <div class="d-flex align-items-center gap-3">
                    <input type="color" 
                        id="color" 
                        name="color" 
                        class="form-control form-control-color @error('color') is-invalid @enderror" 
                        value="{{ old('color', $emotion->color) }}" 
                        title="Choose emotion color">
                <span id="colorHelpInline" class="form-text fst-italic mb-0">
                        Click on the color picker to select the desired shade.
                    </span>
                </div>
                @error('color')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Submit Btn -->
        <div class="d-flex justify-content-end pt-4 mt-4">
            <button type="submit" class="btn-lightblue px-4 py-2">Save Changes</button>
        </div>
    </form>
</div>

@endsection