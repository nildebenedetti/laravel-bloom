@extends('layouts.categories')

@section('title', 'See Category Details')

@section('content')

<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('emotions.index') }}" class="btn bg-light-blue text-secondary">
        Back to All
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper px-5 d-flex justify-content-end align-items-center gap-3">
        <a href="{{ route('emotions.edit', $emotion) }}" class="btn btn-outline-warning">
        Edit
        </a>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $emotion->id }}">
        Delete
        </button>
    </div>
</div>
<div class="page-title-wrapper container pt-4 text-capitalize d-flex flex-column">
    <h4 class="fs-3 text-secondary">
        {{ $emotion->name }}
    </h4>
    <!-- Color -->
    <div class="d-flex justify-content-start align-items-baseline gap-3">
        <h5 class="fs-5 mb-3 text-muted mt-4">Color:</h5>
        <span class="badge p-2 rounded-pill" style="background-color:{{ $emotion->color }}">{{ $emotion->color }}</span>
    </div>
</div>




<!-- Modal for delete-->
<div class="modal fade" id="deleteModal-{{ $emotion->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $emotion->id }}">Delete Emotion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Do you wish to proceed with deletion of Emotion <strong>"{{ $emotion->name }}"</strong>?
            </div>

            <div class="modal-footer">
                {{-- Cancel btn --}}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                {{-- Form for effective delete --}}
                <form action="{{ route('emotions.destroy', $emotion->name) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>


@endsection