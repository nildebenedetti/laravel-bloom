@extends('layouts.users')

@section('title', 'All Users')

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
        <a href="{{ route('users.create') }}" class="btn-lightblue">
            Add New
        </a>
    </div>
</div>

<!-- Table -->
<div class="container w-100 py-3">
    <!-- Tabella con bordi smussati e ombra -->
    <div class="rounded-3 border overflow-hidden shadow-sm">
        <table class="table table-responsive mb-0">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Actions</th>
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
                    <td>
                        <a href="{{ route('users.show', $user) }}" class="btn-lightblue-sm "><i class="bi bi-arrow-right"></i></a> 
                        <a href="{{ route('users.edit', $user) }}" class="btn-lightblue-sm"><i class="bi bi-pencil-fill"></i></a>
                        <button type="button" class="btn-lightblue-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $user->id }}">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for delete -->
@foreach ($users as $user)
    <div class="modal fade" id="deleteModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel-{{ $user->id }}">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    Do you wish to proceed with deletion of User <strong>"{{ $user->name }}"</strong>?
                </div>

                <div class="modal-footer">
                    {{-- Cancel btn --}}
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                    {{-- Form for effective delete --}}
                    <form action="{{ route('users.destroy', $user) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete permanently</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endforeach

@endsection