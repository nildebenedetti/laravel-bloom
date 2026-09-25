@extends('layouts.categories')

@section('title', 'See Category Details')

@section('content')
<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('categories.index') }}" class="btn-lightblue">
        Back to All
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper d-flex justify-content-end align-items-center gap-3">
        <a href="{{ route('categories.edit', $category) }}" class="btn-lightblue">
        Edit
        </a>
        <button type="button" class="btn-lightblue" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $category->id }}">
        Delete
        </button>
    </div>
</div>
<!--white bg wrapper-->
<div class="container bg-white bg-opacity-75 rounded-2 my-3">
    <div class="page-title-wrapper container pt-4 text-capitalize d-flex flex-column">
        <h2 class="page-title text-secondary">
            {{ $category->name }}
        </h2>
        </div>
    <!-- Description -->
    <div class="container text-justify">
        <p>{{ $category->description }}</p>
    </div>
</div>
<!-- Modal for delete-->
<div class="modal fade" id="deleteModal-{{ $category->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $category->id }}">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Do you wish to proceed with deletion of Category <strong>"{{ $category->name }}"</strong>?
            </div>

            <div class="modal-footer">
                {{-- Cancel btn --}}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                {{-- Form for effective delete --}}
                <form action="{{ route('categories.destroy', $category) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>


@endsection