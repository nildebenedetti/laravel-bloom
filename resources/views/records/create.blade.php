@extends('layouts.records')

@section('title', 'Add a New Record ')

@section("content")
    <div class="container form-container p-4">
    <form action="#}" method="POST" class="py-4" enctype="multipart/form-data">
        @csrf {{-- security token for Cross-Site Request Forgery --}}
        <div class="row d-flex justify-content-center">
            <!-- title -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="title" class="pt-2">Title</label>
                <input required type="text" id="title" name="title">
            </div>
            <!-- Date -->
            <div class="col col-sm-12 d-flex flex-column">
                <label for="date" class="pt-2">Date</label>
                <input required type="date" id="date" name="date">
            </div>
            
            <!-- User -->

            <!-- category selection -->

            <!-- emotions -->

            <!-- image input -->
            
            <!-- alt text image input -->       

            <!-- description -->
            <div class="col col-sm-12 col-md-12 col-lg-12 d-flex flex-column">
                <label for="description" class="py-2">Description</label>
                <textarea required id="description" name="description" rows="10" ></textarea>
            </div>
            <!-- Visibility-->
            <!-- <div class="col col-sm-12 d-flex flex-column align-items-end">
                <label for="visibility"></label>
                <select name="visibility" id="visibility">
                    

                </select> -->
            </div>
        </div>
        <div class="btn-wrapper d-flex justify-content-end pt-4">
            <button type="submit" action class="btn btn-outline-primary px-3">Save</button>
        </div>        
            
    </form>
</div>
@endsection