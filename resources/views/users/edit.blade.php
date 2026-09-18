@extends('layouts.users')

@section('title', 'Edit User')

@section('content')

<div class="container form-container p-4">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('users.index') }}" class="btn bg-light-blue text-secondary">
            Back to All
        </a>
    </div>
    <form action="{{ route('users.update', $user) }}" method="POST" class="py-4">
        @csrf {{-- security token for Cross-Site Request Forgery --}}
        @method('PUT')

        <div class="row d-flex justify-content-center g-3">
            <!-- Name -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="name" class="pt-2">Name</label>
                <input required type="text" id="name" name="name" value="{{ old('name', $user->name) }}">
            </div>

            <!-- Email -->
            <div class="col col-sm-12 col-md-6 d-flex flex-column">
                <label for="email" class="pt-2">Email</label>
                <input required type="email" id="email" name="email" value="{{ old('email', $user->email) }}">
            </div>

            <!-- Role -->
            <div class="col col-sm-12 col-md-6 d-flex flex-column">
                <label for="role" class="pt-2">Role</label>
                <select required id="role" name="role" class="form-select">
                    <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <!-- Password (Opzionale in modifica) -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="password" class="pt-2">Password <small class="text-muted">(Leave blank to keep current password)</small></label>
                <input type="password" id="password" name="password">
            </div>

            <!-- Bio (Profile) -->
            <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                <label for="bio" class="py-2">Bio</label>
                <textarea id="bio" name="bio" rows="6">{{ old('bio', $user->profile?->bio) }}</textarea>
            </div>
        </div>

        <div class="btn-wrapper d-flex justify-content-end pt-4">
            <button type="submit" class="btn btn-outline-primary px-3">Update</button>
        </div>            
    </form>
</div>
@endsection