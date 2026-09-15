@extends('layouts.records')

@section('title', 'Detail Page')

@section('content')

<!-- Back to All btn-->
<div class="btn-wrapper px-5 d-flex justify-content-start">
    <a href="{{ route('records.index') }}" class="btn bg-light-blue mt-3 text-secondary">
        Back to All Records
    </a>
</div>

<div class="page-title-wrapper px-5 pt-4 text-capitalize">
    <h4 class="page-title text-secondary">
        {{ $record->title }}
    </h4>
<!-- Category-->

<!--- Emotion Pills-->
</div>
<!-- Image -->
@if($record->image_path)
    <img src="{{ $record->image_path}}" alt="{{$record->title}}">
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