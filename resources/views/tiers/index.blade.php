@extends('layouts.tiers')

@section('title', 'All Tiers')

@section('content')

<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('dashboard') }}" class="btn-lightblue">
            Back to Dashboard
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper d-flex justify-content-end align-items-center">
        <a href="{{ route('tiers.create') }}" class="btn btn-lightblue">
            Add New
        </a>
    </div>
</div>
<div class="container w-100 py-3">
    <div class="rounded-3 border overflow-hidden shadow-sm">
        <table class="table table-responsive mb-0">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Description</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($tiers as $tier) 
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $tier->name }}</td>
                    <td>{{ $tier->description }}</td>
                    <td>
                        <a href="{{ route('tiers.show', $tier) }}" class="btn-lightblue-sm"><i class="bi bi-arrow-right"></i></a> 
                        <a href="{{ route('tiers.edit', $tier) }}" class="btn-lightblue-sm"><i class="bi bi-pencil-fill"></i></a>
                        <button type="button" class="btn-lightblue-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $tier->id }}">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>


<!-- Modal for delete-->
<div class="modal fade" id="deleteModal-{{ $tier->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $tier->id }}">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Do you wish to proceed with deletion of Category <strong>"{{ $tier->name }}"</strong>?
            </div>

            <div class="modal-footer">
                {{-- Cancel btn --}}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                {{-- Form for effective delete --}}
                <form action="{{ route('tiers.destroy', $tier->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection