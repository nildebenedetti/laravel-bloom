@extends('layouts.users')

@section('title', 'All Users')

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
        <a href="{{ route('users.create') }}" class="btn btn-outline-success">
            Add New
        </a>
    </div>
</div>

<div class="container py-3">
    <table class="table">
        <thead>
            <tr>
                <th class="col">#</th>
                <th class="col">Name</th>
                <th class="col">Email</th>
                <th class="col">Role</th>
                <th class="col">Bio</th>
                <th class="col">Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($users as $user) 
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td>{{ $user->profile?->bio ?? 'No bio available' }}</td>
                <td>
                    <a href="{{ route('users.show', $user) }}" class="action-btn btn btn-outline-info"><i class="bi bi-arrow-right"></i></a> 
                    <a href="{{ route('users.edit', $user) }}" class="action-btn btn btn-outline-warning"><i class="bi bi-pencil-fill"></i></a>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $user->id }}">
                        Delete
                    </button>
                </td>
            </tr>

            <!-- Modal for delete  -->
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
        @endforeach
        </tbody>
    </table>
</div>
@endsection