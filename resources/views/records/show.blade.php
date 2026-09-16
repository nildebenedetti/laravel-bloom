@extends('layouts.records')

@section('title', 'Detail Page')

@section('content')


<div class="btns-wrapper container d-flex justify-content-between">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('records.index') }}" class="btn bg-light-blue mt-3 text-secondary">
            Back to All Records
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper px-5 d-flex justify-content-end gap-3">
        <a href="{{ route('records.edit', $record ) }}" class="btn bg-warning mt-3 text-light">
            Edit
        </a>
        <a href="#" class="btn bg-danger mt-3 text-light">
            Delete
        </a>
    </div>
</div>


<div class="page-title-wrapper container pt-4 text-capitalize d-flex flex-column">
    <h4 class="page-title text-secondary">
        {{ $record->title }}
    </h4>
<!-- Category-->

<!--- Emotion Pills-->
</div>
<!-- Image -->
@if($record->image_path)
<div class="container">
    <img src="{{ asset('storage/' . $record->image_path) }}" alt="{{$record->image_alt}}" class="w-75 img-fluid">
</div>
@endif
<!-- Description 
    Preserves original line breaks (\n) using PHP's nl2br().
    - e(): Escapes special characters to prevent XSS vulnerabilities.
    - nl2br(): Converts newline characters into HTML <br> tags.
-->
<div class="container text-justify">
    <p>{!! nl2br(e($record->description)) !!}</p>
</div>

@endsection