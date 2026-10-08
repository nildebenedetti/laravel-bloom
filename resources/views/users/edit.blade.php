@extends('layouts.users')

@section('title', 'Edit User')

@section('content')

<div class="container form-container p-4">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('users.index') }}" class="btn-lightblue">
            Back to All
        </a>
    </div>
        <!--white bg wrapper-->
        <div class="container bg-white bg-opacity-75 rounded-2 my-3">
            <form action="{{ route('users.update', $user) }}" method="POST" class="py-4">
                @csrf {{-- security token for Cross-Site Request Forgery --}}
                @method('PUT')

                <div class="row d-flex justify-content-center g-3">
                    <!-- Name -->
                    <div class="col col-sm-12 d-flex flex-column">
                        <label for="name" class="pt-2">Name</label>
                        <input required maxlength="255" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="col col-sm-12 col-md-6 d-flex flex-column">
                        <label for="email" class="pt-2">Email</label>
                        <input required type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div class="col col-sm-12 col-md-6 d-flex flex-column">
                        <label for="role" class="pt-2">Role</label>
                        <select required id="role" name="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password (Opzionale in modifica) -->
                    <div class="col col-sm-12 d-flex flex-column">
                        <label for="password" class="pt-2">Password <small class="text-muted">(Leave blank to keep current password)</small></label>
                        <input minlength="8" type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Bio (Profile) -->
                    <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                        <label for="bio" class="py-2">Bio <small class="text-muted">(optional)</small></label>
                        <textarea id="bio" name="bio" rows="6" class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $user->profile?->bio) }}</textarea>
                        @error('bio')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="btn-wrapper d-flex justify-content-end pt-4">
                    <button type="submit" class="btn-lightblue">Update</button>
                </div>            
            </form>
        </div>
</div>
@endsection