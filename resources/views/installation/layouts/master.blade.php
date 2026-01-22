<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{dynamicAsset('public/assets/installation/assets/img/favicon.png')}}">

    <!-- Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{dynamicAsset('public/assets/admin-module/css/fonts/google.css')}}"/>

    <link rel="stylesheet" href="{{dynamicAsset('public/assets/installation/assets/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{dynamicAsset('public/assets/installation/assets/css/style.css')}}">
    <link rel="stylesheet" href="{{dynamicAsset('public/assets/admin-module/css/toastr.css')}}"/>

</head>

<body>
<section style="background-image: url('{{dynamicAsset('public/assets/installation')}}/assets/img/page-bg.png')"
         class="w-100 min-vh-100 bg-img position-relative py-5">

    <div class="position-absolute" style="top: 1.5rem; right: 1.5rem; z-index: 10;">
        <button type="button" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center"
                style="width: 42px; height: 42px;"
                data-bs-toggle="modal" data-bs-target="#developerDetailsModal" aria-label="Developer details">
            <span class="fw-bold" style="font-size: 18px; line-height: 1;">i</span>
        </button>
    </div>

    <div class="custom-container">
        @yield('content')

        <!-- Footer -->
        <footer class="footer py-3 mt-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 align-items-center">
                <p class="copyright-text mb-0">© {{date("Y")}} | {{translate('All Rights Reserved')}}</p>
            </div>
        </footer>
    </div>
</section>

<div class="modal fade" id="developerDetailsModal" tabindex="-1" aria-labelledby="developerDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="developerDetailsModalLabel">Developer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="p-3 rounded" style="background: rgba(0,0,0,0.03);">
                    <div class="fw-semibold" style="font-size: 16px;">Ray Developer</div>
                    <div class="text-muted" style="font-size: 14px;">Phone: <a href="tel:+255742710054">+255742710054</a></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-dark px-sm-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Script Goes Here -->
<script src="{{dynamicAsset('public/assets/installation/assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{dynamicAsset('public/assets/installation/assets/js/script.js')}}"></script>
<script src="{{dynamicAsset('public/assets/admin-module/js/jquery-3.6.0.min.js')}}"></script>
<script src="{{dynamicAsset('public/assets/admin-module/js/toastr.js')}}"></script>
{!! Toastr::message() !!}

@if ($errors->any())
    <script>
        "use strict";
        @foreach($errors->all() as $error)
        toastr.error('{{$error}}', Error, {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif
@stack('script')
</body>
</html>
