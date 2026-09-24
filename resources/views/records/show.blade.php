@extends('layouts.records')

@section('title', 'Detail Page')

@section('content')

<div class="container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('admin.records.index') }}" class="btn-lightblue">
            Back to All Records
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper d-flex justify-content-end align-items-center gap-3">
        <a href="{{ route('admin.records.edit', $record ) }}" class="btn btn-outline-warning bg-white bg-opacity-75">
            Edit
        </a>
        <button type="button" class="btn btn-outline-danger bg-white bg-opacity-75" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $record->id }}">
            Delete
        </button>
    </div>
</div>
<!--white bg wrapper-->
<div class="container bg-white bg-opacity-75 rounded-2 mx-5 my-3">
    <div class="page-title-wrapper container pt-4 text-capitalize d-flex flex-column">
        <h4 class="page-title text-secondary">
            {{ $record->title }}
        </h4>
    <!-- Category-->
    <p class="fs-5 text-muted">{{ $record->category->name}}</p>

    <!--- Emotion Pills-->
    <div class="d-flex gap-2 mb-4">
        @if(count($record->emotions) > 0)
        @foreach($record->emotions as $emotion)
        <span class="badge rounder-pill" style="background-color:{{ $emotion->color }}">{{ $emotion->name }}</span>
        @endforeach
        @endif
    </div>
</div>
    <div class="row g-4 align-items-start pb-4">
        <!-- Image -->
        @if($record->image_path)
        <div class="col-12 col-lg-4">
            <img src="{{ asset('storage/' . $record->image_path) }}" alt="{{$record->image_alt}}" class="w-100 img-fluid rounded-2">
        </div>
        @endif
        <!-- Description 
            Preserves original line breaks (\n) using PHP's nl2br().
            - e(): Escapes special characters to prevent XSS vulnerabilities.
            - nl2br(): Converts newline characters into HTML <br> tags.
        -->
        <div class="col-12 {{ $record->image_path ? 'col-lg-8' : '' }} fst-italic">
            <p>{!! nl2br(e($record->description)) !!}</p>
        </div>
    </div>
</div>

<!-- Modal for delete-->

<div class="modal fade" id="deleteModal-{{ $record->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $record->id }}">Delete Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Do you wish to proceed with deletion of Record <strong>"{{ $record->title }}"</strong>?
            </div>

            <div class="modal-footer">
                {{-- Cancel btn --}}
                <button type="button" class="btn-lightblue" data-bs-dismiss="modal">Cancel</button>

                {{-- Form for effective delete --}}
                <form action="{{ route('admin.records.destroy', $record) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection