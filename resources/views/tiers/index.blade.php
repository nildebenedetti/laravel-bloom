@extends('layouts.tiers')

@section('title', 'All Tiers')

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
        <a href="{{ route('tiers.create') }}" class="btn btn-outline-success">
            Add New
        </a>
    </div>
</div>
<div class="container py-3">
    <table class="table">
        <thead>
            <th>#</th>
            <th>Name</th>
            <th>Description</th>
            <th>Action</th>
        </thead>
        <tbody>
            @foreach($tiers as $tier)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $tier->name }}</td>
                <td>{{ $tier->description }}</td>
                <td>
                    <a href="{{ route('tiers.show', $tier) }}" class="action-btn btn btn-outline-info"><i class="bi bi-arrow-right"></i></a> 
                        <a href="#" class="action-btn btn btn-outline-warning"><i class="bi bi-pencil-fill"></i></a>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#">
                        Delete
                        </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection