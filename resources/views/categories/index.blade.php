@extends('layouts.categories')

@section('title', 'All Categories')

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
        <a href="#" class="btn btn-outline-success">
            Add New
        </a>
    </div>
</div>
<div class="container py-3">
    <table class="table">
        <thead>
            <th class="col">#</th>
            <th class="col">Name</th>
            <th class="col">Description</th>
            <th class="col">Actions</th>
        </thead>
        <tbody>
        @foreach ($categories as $category) 
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
                <td>
                    <a href="{{ route('categories.show', $category)}}" class="action-btn btn btn-outline-info"><i class="bi bi-arrow-right"></i></a> 
                        <a href="#" class="action-btn btn btn-outline-warning"><i class="bi bi-pencil-fill"></i></a>
                        <button type="button" class="btn btn-outline-danger">
                                <i class="bi bi-trash3-fill"></i>
                        </button>   
                </td>
            </tr>
        
        @endforeach
        </tbody>
    </table>
</div>

@endsection