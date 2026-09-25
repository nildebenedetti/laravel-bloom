@props(['record'])

<div class="col">
    <div class="card h-100">
        <div class="card-body">
            <div class="pt-2 d-flex align-items-baseline justify-content-between">
                <h4 class="card-title">{{ $record->title }}</h4>
                <small class="fst-italic text-muted"><i class="bi bi-calendar3 me-1"></i>{{ $record->date->format('d/m/Y') }}</small>
            </div>
            <div class="pt-2">
                <small class="fst-italic text-muted">{{ $record->user->name }}</small>
            </div>
            <div class="btn-wrapper d-flex justify-content-end pt-4">
                <a href="{{ route('admin.records.show', $record) }}" class="btn-lightblue-sm">
                    <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
            
        </div>
    </div>
</div>