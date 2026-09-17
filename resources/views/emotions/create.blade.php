@extends('layouts.emotions')

@section('title', 'Add a New Emotion')

@section('content')

<div class="container form-container p-4">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('emotions.index') }}" class="btn bg-light-blue text-secondary">
        Back to All
        </a>
    </div>
    <form action="{{ route('emotions.store') }}" method="POST" class="pt-4">
        @csrf {{-- security token for Cross-Site Request Forgery --}}
        <div class="row d-flex justify-content-start">
        <!-- Name -->
        <div class="col col-sm-12 col-lg-6 col-xl-4 d-flex flex-column">
            <label for="name" class="py-2">Name</label>
            <input type="text" id="name" name="name">
        </div>
        <!-- Color -->
        <div class="col col-sm-12 col-lg-6 col-xl-4 d-flex flex-column">
            <label for="color" class="pt-2">Color</label>
            <div class="helper-text-wrapper py-2">
            <span id="colorHelpInline" class="form-text fst-italic">
                Click on the color picker and select the desired shade.
            </span>
        </div>
        <!-- input type color picks hexadecimal value! -->
            <input type="color" id="color" name="color">
        <!-- submit btn -->
        <div class="btn-wrapper d-flex justify-content-end pt-4">
            <button type="submit" class="btn btn-outline-primary px-3">Save</button>
        </div>   
    </form>
</div>

@endsection