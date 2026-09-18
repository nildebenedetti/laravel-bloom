@extends("layouts.app")

@section("title", "Dashboard")

@section("content")
    <div class="page-header container py-4 text-secondary">
        <h2>Welcome in the backoffice!</h2>
        <h5 class="pt-2">Behind the scenes, where the magic takes place...</h5>
        <p class="text-muted pt-3">
            Access the tools below to view, create, edit, and manage system content. 
            <br>
            Please act wisely — your changes directly impact our Community.
        </p>
    </div>
    
    <div class="container cards-container pb-4">
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 justify-content-center">
            <!-- Users -->
            <x-page-access-card>
                <x-slot:icon>
                    <i class="bi bi-person"></i>
                </x-slot:icon>
                <x-slot:title>Users</x-slot:title>
                <x-slot:description>Our Community
                </x-slot:description>
            </x-page-access-card>
            <!-- Records -->
            <x-page-access-card :route="route('records.index')">
                <x-slot:icon>
                    <i class="bi bi-journal-richtext"></i>
                </x-slot:icon>
                <x-slot:title>Records</x-slot:title>
                <x-slot:description>The achievements of our users
                </x-slot:description>
            </x-page-access-card>
            <!-- Categories -->
            <x-page-access-card :route="route('categories.index')">
                <x-slot:icon>
                    <i class="bi bi-tag"></i>
                </x-slot:icon>
                <x-slot:title>Categories</x-slot:title>
                <x-slot:description>The categories to which the achievements belong
                </x-slot:description>
            </x-page-access-card>
            <!-- Tiers -->
            <x-page-access-card :route="route('tiers.index')">
                <x-slot:icon>
                    <i class="bi bi-trophy"></i>
                </x-slot:icon>
                <x-slot:title>Tiers</x-slot:title>
                <x-slot:description>The impact of the achievements
                </x-slot:description>
            </x-page-access-card>
            <!-- Emotions -->
            <x-page-access-card :route="route('emotions.index')">
                <x-slot:icon>
                    <i class="bi bi-balloon-heart"></i>
                </x-slot:icon>
                <x-slot:title>Emotions</x-slot:title>
                <x-slot:description>How the achievement makes the User feel
                </x-slot:description>
            </x-page-access-card>
        </div>
    </div>
    
@endsection