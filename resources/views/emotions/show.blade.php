@extends('layouts.categories')

@section('title', 'See Emotion Details')

@section('content')

<!-- Header Action Buttons -->
<div class="container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('emotions.index') }}" class="btn-lightblue">
            Back to All
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper d-flex justify-content-end align-items-center gap-3">
        <a href="{{ route('emotions.edit', $emotion) }}" class="btn-lightblue">
            Edit
        </a>
        <button type="button" class="btn-lightblue" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $emotion->id }}">
            Delete
        </button>
    </div>
</div>

<!-- White Mask Wrapper -->
<div class="container bg-white bg-opacity-75 rounded-3 border shadow-sm my-3 p-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 pb-3">
        <div>
            <h3 class="text-secondary text-capitalize mb-1">
                {{ $emotion->name }}
            </h3>
            <span class="text-muted fs-6">Emotion Detail</span>
        </div>

        <!-- Color Badge Indicator -->
        <div class="d-flex align-items-center gap-2 bg-white p-2 px-3 rounded-pill border">
            <span class="fw-semibold text-secondary">Color:</span>
            <span class="badge p-2 rounded-pill shadow-sm" style="background-color: {{ $emotion->color }}; min-width: 90px;">
                {{ $emotion->color }}
            </span>
        </div>
    </div>

    <!-- Description -->
    @if(isset($emotion->description) && $emotion->description)
    <div class="pt-4 fst-italic text-secondary">
        <p class="mb-0">{!! nl2br(e($emotion->description)) !!}</p>
    </div>
    @endif
</div>

<!-- Modal for delete -->
<div class="modal fade" id="deleteModal-{{ $emotion->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $emotion->id }}">Delete Emotion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                Do you wish to proceed with deletion of Emotion <strong>"{{ $emotion->name }}"</strong>?
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                {{-- Form for effective delete --}}
                <form action="{{ route('emotions.destroy', $emotion) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection