@props(['record'])

<div class="col">
    <div class="card h-100">
        <div class="card-body">
            <h4 class="card-title">{{ $record->title }}</h4>
            <!-- add pills for emotions somewhere here -->
            <!-- add user name -->
            <!-- Category-->
            <p class="card-subtitle">category</p>
            <div class="btn-wrapper d-flex justify-content-end">
                <a href="{{ route('records.show', $record) }}" class="btn bg-light-blue mt-3">
                    <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
            
        </div>
    </div>
</div>