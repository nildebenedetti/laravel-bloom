@extends('layouts.records')

@section('title', 'Edit Record ')

@section("content")
    <div class="container form-container p-4">
            <!-- Back to resource-->
    <div class="btn-wrapper d-flex justify-content-star p-2">
        <a href="{{ route('admin.records.show', $record) }}" class="btn-lightblue">
        Back to <b>{{ $record->name }}</b>
        </a>
    </div>
        <!--white bg wrapper-->
    <div class="container bg-white bg-opacity-75 rounded-2 mx-2 my-3">
        <form action="{{ route('admin.records.update', $record ) }}" method="POST" class="py-4" enctype="multipart/form-data">
            @csrf {{-- security token for Cross-Site Request Forgery --}}
            @method('PUT') {{-- METHOD DIRECTIVE --}}
            <div class="row d-flex justify-content-center">
                <!-- title -->
                <div class="col col-sm-12 d-flex flex-column">
                    <label for="title" class="pt-2">Title</label>
                    <input required type="text" id="title" name="title" value="{{ $record->title }}">
                </div>
                <!-- Date -->
                <div class="col col-sm-12 d-flex flex-column">
                    <label for="date" class="pt-2">Date</label>
                    <input required 
                    type="date" 
                    id="date" 
                    name="date" 
                    value="{{ old('date', $record->date?->format('Y-m-d')) }}">
                </div>
                
                <!-- User -->

                <!-- category selection (1:N) -->
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                {{ old('category_id', $record->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <!-- image input -->
                <div class="col col-sm-12 mb-3 d-flex flex-wrap mx-4 mt-4 gap-3 align-items-baseline">
                    <label for="image_path">Add an image to this record</label>
                    <input type="file" name="image_path" id="image_path" >
                </div>

                @if($record->image_path)
                <div id="record-image">
                    <img src="{{ asset('storage/' . $record->image_path) }}" alt="{{ $record->image_alt }}" class="img-fluid w-25 rounded-2 m-2">
                </div>
                @endif
                <!-- alt text image input -->
                <div class="col col-sm-12 d-flex flex-column mb-3">
                    <label class="pb-3" for="image_alt">Add a description for your image.</label>
                    <input type="text" name="image_alt" id="image_alt" value="{{ $record->image_alt }}">
                </div>        

                <!-- description -->
                <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                    <label for="description" class="py-2"></label>
                    <textarea required id="description" name="description" rows="10" >{{ $record->description }}</textarea>
                </div>

                <!-- emotions selection (N:N) -->
                <div class="mb-3 mt-2">
                    <label class="form-label d-block">Emotions</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($emotions as $emotion)
                            <div class="form-check">
                                <input type="checkbox" 
                                    name="emotions[]" 
                                    value="{{ $emotion->id }}"
                                    id="emotion-{{ $emotion->id }}"
                                    class="form-check-input"
                                    {{ in_array($emotion->id, old('emotions', $record->emotions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                <label for="emotion-{{ $emotion->id }}" class="form-check-label">
                                    {{ $emotion->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- Visibility
                * Dynamically marks the option as selected if its value matches 
                the old form input or the record's current visibility value-->
                <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column py-3">
                        <select name="visibility" id="visibility">
                        @foreach(\App\Enums\RecordVisibility::cases() as $visibility)
                        <option 
                            value="{{ $visibility->value }}"
                            @selected(old('visibility', $record->visibility?->value) === $visibility->value)>
                            {{ $visibility->label() }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="btn-wrapper d-flex justify-content-end pt-4">
                <button type="submit" action class="btn-lightblue">Save</button>
            </div>        
                
        </form>
</div>
</div>
@endsection