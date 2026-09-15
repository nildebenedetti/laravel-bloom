@extends('layouts.app')
@section('content')

    <div class="page-header container py-4 text-secondary">
        <h2>Bloom Backoffice</h2>
        <p class="pt-2 text-secondary">Access is restricted to our staff only.</p>
        
        <div class="d-flex justify-content-center py-5">
                <a href="{{ route('login') }}" class="btn bg-light-blue text-secondary">
                    Go to Login
                </a>
        </div>
    </div>
@endsection