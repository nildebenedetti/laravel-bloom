@extends('layouts.records')

@section('title', 'Detail Page')

@section('content')


<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('records.index') }}" class="btn bg-light-blue text-secondary">
            Back to All Records
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper px-5 d-flex justify-content-end align-items-center gap-3">
        <a href="{{ route('records.edit', $record ) }}" class="btn btn-outline-warning">
            Edit
        </a>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $record->id }}">
            Delete
        </button>
    </div>
</div>
<div class="page-title-wrapper container pt-4 text-capitalize d-flex flex-column">
    <h4 class="page-title text-secondary">
        {{ $record->title }}
    </h4>
<!-- Category-->
<p class="fs-5 text-muted">{{ $record->category->name}}</p>

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
<div class="container text-justify fst-italic">
    <p>{!! nl2br(e($record->description)) !!}</p>
</div>

<!-- Modal for delete-->

<div class="modal fade" id="deleteModal-{{ $record->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $record->id }}">Delete Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Do you wish to proceed with deletion of Record <strong>"{{ $record->title }}"</strong>?
            </div>

            <div class="modal-footer">
                {{-- Cancel btn --}}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                {{-- Form for effective delete --}}
                <form action="{{ route('records.destroy', $record) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection