@extends('layouts.users')

@section('title', 'See User Details')

@section('content')
<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('users.index') }}" class="btn bg-light-blue text-secondary">
            Back to All
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper px-5 d-flex justify-content-end align-items-center gap-3">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-warning">
            Edit
        </a>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $user->id }}">
            Delete
        </button>
    </div>
</div>

<div class="page-title-wrapper container pt-4 d-flex flex-column">
    <div class="d-flex align-items-center gap-3">
        <h4 class="page-title text-secondary mb-0 text-capitalize">
            {{ $user->name }}
        </h4>
        <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
            {{ ucfirst($user->role) }}
        </span>
    </div>
    <span class="text-muted mt-2">{{ $user->email }}</span>
</div>

<!-- Bio -->
<div class="container text-justify mt-4">
    <h6 class="text-secondary fw-bold">Bio</h6>
    <p>{{ $user->profile?->bio ?? 'No bio available.' }}</p>
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