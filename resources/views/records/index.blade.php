@extends('layouts.records')

@section('title', 'All Records')

@section('content')
<!-- Back to All btn-->
    <div class="btn-wrapper px-5 d-flex justify-content-start">
        <a href="{{ route('dashboard') }}" class="btn bg-light-blue mt-3 text-secondary">
            Back to Dashboard
        </a>
    </div>
    <div class="container py-5">
        <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3 g-3">
            @foreach($records as $record)
            <x-record-card :record="$record"></x-record-card>
            @endforeach
        </div>
    </div>
@endsection