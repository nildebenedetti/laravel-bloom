@extends('layouts.records')

@section('title', 'All Records')

@section('content')
    <div class="container py-5">
        <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3 g-3">
            @foreach($records as $record)
            <x-record-card :record="$record"></x-record-card>
            @endforeach
        </div>
    </div>
@endsection