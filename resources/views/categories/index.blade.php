@extends('layouts.categories')

@section('title', 'All Categories')

@section('content')
    <div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('dashboard') }}" class="btn bg-light-blue text-secondary">
            Back to Dashboard
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper px-5 d-flex justify-content-end align-items-center">
        <a href="{{ route('categories.create') }}" class="btn btn-outline-success">
            Add New
        </a>
    </div>
</div>
<div class="container py-3">
    <table class="table">
        <thead>
            <th class="col">#</th>
            <th class="col">Name</th>
            <th class="col">Description</th>
            <th class="col">Actions</th>
        </thead>
        <tbody>
        @foreach ($categories as $category) 
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
                <td>
                    <a href="{{ route('categories.show', $category)}}" class="action-btn btn btn-outline-info"><i class="bi bi-arrow-right"></i></a> 
                        <a href="{{ route('categories.edit', $category) }}" class="action-btn btn btn-outline-warning"><i class="bi bi-pencil-fill"></i></a>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $category->id }}">
                        Delete
                        </button>
                </td>
            </tr>
        
        @endforeach
        </tbody>
    </table>
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
                <form action="{{ route('categories.destroy', $category->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>


@endsection