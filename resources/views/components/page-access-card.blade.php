
@props(['route' => '#'])

<div class="col">
    <div class="page-access-card card h-100 shadow-sm">
        <div class="card-body">
            <div class="container head-container p-3 d-flex wv-50  align-items-baseline">
                <span class="icon px-2">
                    {{ $icon }}
                </span>
            <h5 class="card-title">{{ $title }}</h5>
            </div>
            
            <p class="card-text">{{ $description }}</p>
            <div class="wrapper d-flex justify-content-end">
                <a href="{{ $route }}" class="btn bg-light-blue">
                    <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </div>
</div>