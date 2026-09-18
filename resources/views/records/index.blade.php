@extends('layouts.records')

@section('title', 'All Records')

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
        <a href="{{ route('records.create' ) }}" class="btn btn-outline-success">
            Add New
        </a>
    </div>
</div>
    <div class="container py-5">
        <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3 g-3">
            @foreach($records as $record)
            <x-record-card :record="$record"></x-record-card>
            @endforeach
        </div>
    </div>
@endsection