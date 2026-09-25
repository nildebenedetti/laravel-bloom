<header>
    <nav class="navbar navbar-expand-lg glass-bar fixed-top">
        <div class="container-fluid px-3">
            <!--- logo -->
            <a class="navbar-brand" href="{{ url('/') }}">
                <img class="navbar-logo" src="{{ Vite::asset('resources/images/logos/bloom-logo.svg') }}" alt="Bloom logo">
            </a>

            <!--- hamburger -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                @auth
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a href="{{ route('dashboard')}}" class="nav-link">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('categories.index')}}" class="nav-link">Users</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.records.index')}}" class="nav-link">Records</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('users.index')}}" class="nav-link">Categories</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('tiers.index')}}" class="nav-link">Tiers</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('emotions.index')}}" class="nav-link">Emotions</a>
                        </li>
                    </ul>
                @endauth
    
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                @if (Route::has('dashboard'))
                                    <a class="dropdown-item" href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                                @endif
                                @if (Route::has('profile.edit'))
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('Profile') }}</a>
                                @endif
                                
                                <hr class="dropdown-divider">

                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
</header>