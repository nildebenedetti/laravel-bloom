@extends('layouts.app')
@section('content')
    <div class="w-100 p-3 p-md-4">
        <div class="page-header container p-5 rounded-2 text-secondary mx-auto d-flex flex-column align-items-center bg-white bg-opacity-75 m-5">
            <h2>Bloom Backoffice</h2>
            <p class="pt-2 text-secondary">Access is restricted to our staff only.</p>
            @guest
                <div class="d-flex justify-content-center py-5">
                    <a href="{{ route('login') }}" class="btn-lightblue">
                        Go to Login
                    </a>
                </div>
            @endguest        
        </div>
    </DIV
@endsection