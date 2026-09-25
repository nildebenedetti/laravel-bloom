@extends("layouts.emotions")

@section('title', 'Emotions')

@section("content")
<div class="btns-wrapper container d-flex justify-content-between mt-3">
    <!-- Back to All btn-->
    <div class="btn-wrapper d-flex justify-content-start">
        <a href="{{ route('dashboard') }}" class="btn-lightblue">
            Back to Dashboard
        </a>
    </div>
    <!-- Resource Interaction Btns-->
    <div class="btn-wrapper d-flex justify-content-end align-items-center">
        <a href="{{ route('emotions.create') }}" class="btn btn-lightblue">
            Add New
        </a>
    </div>
</div>
    <!-- Table -->
    <div class="container w-100 py-3">
    <!-- Tabella con bordi smussati e ombra -->
    <div class="rounded-3 border overflow-hidden shadow-sm">
        <table class="table table-responsive mb-0">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Color Code</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($emotions as $emotion) 
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $emotion->name }}</td>
                    <td>
                        <span class="badge p-2 rounded-pill" style="background-color: {{ $emotion->color }}">{{ $emotion->color }}</span>
                    </td>
                    <td>
                        <a href="{{ route('emotions.show', $emotion) }}" class="btn-lightblue-sm"><i class="bi bi-arrow-right"></i></a> 
                        <a href="{{ route('emotions.edit', $emotion) }}" class="btn-lightblue-sm"><i class="bi bi-pencil-fill"></i></a>
                        <button type="button" class="btn-lightblue-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $emotion->id }}">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Table -->
@foreach ($emotions as $emotion)
    <div class="modal fade" id="deleteModal-{{ $emotion->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel-{{ $emotion->id }}">Delete Emotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    Do you wish to proceed with deletion of Emotion <strong>"{{ $emotion->name }}"</strong>?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                    <form action="{{ route('emotions.destroy', $emotion) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete permanently</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endforeach
</div>
@endsection