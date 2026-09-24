@extends('layouts.app')
@section('content')

    <div class="page-header container py-4 text-secondary mx-auto d-flex flex-column align-items-center">
        <h2>Bloom Backoffice</h2>
        <p class="pt-2 text-secondary">Access is restricted to our staff only.</p>
        @guest
            <div class="d-flex justify-content-center py-5">
                <a href="{{ route('login') }}" class="btn bg-light-blue text-dark">
                    Go to Login
                </a>
            </div>
        @endguest        
    </div>
@endsection