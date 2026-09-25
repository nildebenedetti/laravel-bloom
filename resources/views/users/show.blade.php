@extends('layouts.users')

@section('title', 'See User Details')

@section('content')
<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('users.index') }}" class="btn-lightblue">
            Back to All
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper d-flex justify-content-end align-items-center gap-3">
        <a href="{{ route('users.edit', $user) }}" class="btn-lightblue">
            Edit
        </a>
        <button type="button" class="btn-lightblue" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $user->id }}">
            Delete
        </button>
    </div>
</div>

<!--white bg wrapper-->
<div class="container bg-white bg-opacity-75 rounded-3 border shadow-sm my-3 p-4">
    <div class="page-title-wrapper pb-3 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <h3 class="page-title text-secondary mb-0 text-capitalize">
                {{ $user->name }}
            </h3>
            <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
                {{ ucfirst($user->role) }}
            </span>
        </div>
        <span class="text-muted d-block mt-2">{{ $user->email }}</span>
    </div>

    <!-- Bio -->
    <div class="pt-4 text-justify">
        <h6 class="text-secondary fw-bold mb-2">Bio</h6>
        <p class="mb-0">{{ $user->profile?->bio ?? 'No bio available.' }}</p>
    </div>
</div>

<!-- Modal for delete-->
<div class="modal fade" id="deleteModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $user->id }}">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Do you wish to proceed with deletion of User <strong>"{{ $user->name }}"</strong>?
            </div>

            <div class="modal-footer">
                {{-- Cancel btn --}}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                {{-- Form for effective delete --}}
                <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete permanently</button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection