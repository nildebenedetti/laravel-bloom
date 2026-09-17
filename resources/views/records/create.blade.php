@extends('layouts.records')

@section('title', 'Add a New Record ')

@section("content")
    <div class="container form-container p-4">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('records.index') }}" class="btn bg-light-blue text-secondary">
        Back to All
        </a>
    </div>
    <form action="{{ route('records.store') }}" method="POST" class="py-4" enctype="multipart/form-data">
        @csrf {{-- security token for Cross-Site Request Forgery --}}
        <div class="row d-flex justify-content-center">
            <!-- title -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="title" class="pt-2">Title</label>
                <input required type="text" id="title" name="title">
            </div>
            <!-- Date -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="date" class="pt-2">Date</label>
                <input required type="date" id="date" name="date">
            </div>
            
            <!-- User -->

            <!-- category selection -->

            <!-- emotions -->

            <!-- image input -->
            <div class="col col-sm-12 mb-3 d-flex flex-wrap mx-4 mt-4 gap-3 align-items-baseline">
                <label for="image_path">Add an image to this record</label>
                <input type="file" name="image_path" id="image_path">
            </div>
            <!-- alt text image input -->
            <div class="col col-sm-12 d-flex flex-column mb-3">
                <label class="pb-3" for="image_alt">Add a description for your image.</label>
                <input type="text" name="image_alt" id="image_alt">
            </div>        

            <!-- description -->
            <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                <label for="description" class="py-2">Description</label>
                <textarea required id="description" name="description" rows="10" ></textarea>
            </div>
            <!-- Visibility-->
            <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column py-3">
                    <select name="visibility" id="visibility">
                    @foreach(\App\Enums\RecordVisibility::cases() as $visibility)
                    <option 
                        value="{{ $visibility->value }}">
                        {{ $visibility->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="btn-wrapper d-flex justify-content-end pt-4">
            <button type="submit" action class="btn btn-outline-primary px-3">Save</button>
        </div>        
            
    </form>
</div>
@endsection