@extends('adminmodule::layouts.master')

@section('title', translate('Google_Map_API'))

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="fs-22 mb-4 text-capitalize">{{translate('3rd_party')}}</h2>
            @include('businessmanagement::admin.configuration.partials._third_party_inline_menu')

            <div class="card">
                <div class="card-body">
                    <h5 class="text-primary text-uppercase mb-4">{{translate('google_map_api_setup')}}</h5>

                    <div
                        class="media align-items-center gap-3 px-3 py-2 rounded border border-primary-light border-start-5 mb-30">
                        <i class="bi bi-info-circle-fill fs-20 text-primary"></i>
                        <p class="media-body"><strong>{{translate('NB')}}
                                :</strong> {{translate('Client key should have enable map javascript api and you can restrict it with http refer')}}
                            .
                            {{translate('Server key should have enable place api key and you can restrict it with ip')}}
                            . {{translate('You can use same api for both field without any restrictions')}}.</p>
                    </div>

                    <form action="{{route('admin.business.configuration.third-party.google-map.update')}}" method="post"
                          id="map_form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="map_api_key" class="mb-2">{{translate('map_API_key')}}
                                        ({{translate('client')}})</label>
                                    <input required type="text" name="map_api_key"
                                           value="{{$setting['map_api_key']??''}}" class="form-control" id="map_api_key"
                                           placeholder="Map API Key" tabindex="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="map_api_key_server" class="mb-2">{{translate('map_API_key')}}
                                        ({{translate('server')}})</label>
                                    <input required type="text" name="map_api_key_server"
                                           value="{{$setting['map_api_key_server']??''}}" class="form-control"
                                           id="map_api_key_server" placeholder="Map API Key" tabindex="2">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}" class="btn btn-primary call-demo" tabindex="3">{{translate('save')}}</button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h6 class="mb-2">{{ translate('map_preview') }}</h6>
                        <div id="gm_preview_notice" class="text-muted mb-2" style="font-size: 13px;"></div>
                        <div id="gm_preview" style="width: 100%; height: 360px; border-radius: 10px; overflow: hidden; border: 1px solid rgba(0,0,0,.08);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Main Content -->
@endsection


@push('script')

    <script>
        "use strict";

        let permission = false;
        @can('business_edit')
            permission = true;
        @endcan


        $('#map_form').on('submit', function (e) {
            if (!permission) {
                toastr.error('{{ translate('you_do_not_have_enough_permission_to_update_this_settings') }}');
                e.preventDefault();
            }
        });

        (function () {
            const notice = document.getElementById('gm_preview_notice');
            const mapEl = document.getElementById('gm_preview');
            const keyInput = document.getElementById('map_api_key');

            let map = null;
            let marker = null;
            let scriptLoadedForKey = null;

            function setNotice(message) {
                if (notice) {
                    notice.textContent = message || '';
                }
            }

            function loadGoogleMapsScript(apiKey) {
                return new Promise(function (resolve, reject) {
                    if (!apiKey) {
                        reject(new Error('Missing API key'));
                        return;
                    }

                    if (window.google && window.google.maps && scriptLoadedForKey === apiKey) {
                        resolve();
                        return;
                    }

                    if (document.getElementById('gmaps_script')) {
                        document.getElementById('gmaps_script').remove();
                    }
                    delete window.google;

                    const script = document.createElement('script');
                    script.id = 'gmaps_script';
                    script.async = true;
                    script.defer = true;
                    script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey);
                    script.onload = function () {
                        scriptLoadedForKey = apiKey;
                        resolve();
                    };
                    script.onerror = function () {
                        reject(new Error('Failed to load Google Maps'));
                    };
                    document.head.appendChild(script);
                });
            }

            function initMapAt(lat, lng) {
                if (!mapEl || !(window.google && window.google.maps)) {
                    return;
                }

                const pos = { lat: lat, lng: lng };

                if (!map) {
                    map = new google.maps.Map(mapEl, {
                        center: pos,
                        zoom: 15,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: true,
                    });
                } else {
                    map.setCenter(pos);
                }

                if (!marker) {
                    marker = new google.maps.Marker({
                        position: pos,
                        map: map,
                    });
                } else {
                    marker.setPosition(pos);
                    marker.setMap(map);
                }
            }

            function requestDeviceLocation() {
                if (!navigator.geolocation) {
                    setNotice('Geolocation is not supported by this browser.');
                    initMapAt(-6.7924, 39.2083);
                    return;
                }

                setNotice('Detecting your current location...');
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        setNotice('Showing your current location.');
                        initMapAt(position.coords.latitude, position.coords.longitude);
                    },
                    function () {
                        setNotice('Location permission denied. Showing default location.');
                        initMapAt(-6.7924, 39.2083);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0,
                    }
                );
            }

            function boot() {
                const apiKey = (keyInput && keyInput.value) ? keyInput.value.trim() : '';

                if (!apiKey) {
                    setNotice('Add a valid client key to preview the map.');
                    return;
                }

                setNotice('Loading map preview...');
                loadGoogleMapsScript(apiKey)
                    .then(function () {
                        requestDeviceLocation();
                    })
                    .catch(function (err) {
                        setNotice((err && err.message) ? err.message : 'Unable to load map.');
                    });
            }

            if (keyInput) {
                keyInput.addEventListener('change', function () {
                    map = null;
                    marker = null;
                    scriptLoadedForKey = null;
                    boot();
                });
            }

            boot();
        })();
    </script>

@endpush
