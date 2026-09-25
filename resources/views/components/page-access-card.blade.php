
@props(['route' => '#'])

<div class="col">
    <div class="page-access-card card h-100 shadow-sm">
        <div class="card-body">
            <div class="container head-container p-2 d-flex wv-50  align-items-baseline">
                <span class="icon px-2">
                    {{ $icon }}
                </span>
            <h5 class="card-title">{{ $title }}</h5>
            </div>
            <div class="container">
                <p class="card-text">{{ $description }}</p>
            </div>

            <div class="wrapper d-flex justify-content-end">
                <a href="{{ $route }}" class="btn-lightblue">
                    <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </div>
</div>