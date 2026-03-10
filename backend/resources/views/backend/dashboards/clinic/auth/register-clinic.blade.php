<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="images/icons/favicon.ico" />
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/vendor/bootstrap/css/bootstrap.min.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/fonts/font-awesome-4.7.0/css/font-awesome.min.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/fonts/iconic/css/material-design-iconic-font.min.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/vendor/animate/animate.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/vendor/css-hamburgers/hamburgers.min.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/vendor/animsition/css/animsition.min.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/vendor/select2/select2.min.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/vendor/daterangepicker/daterangepicker.css')}}">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('login/css/util.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('login/css/main.css')}}">
    <!--===============================================================================================-->
    <style>
        .required::after {
            content: " *";
            color: red;
            font-weight: bold;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .is-invalid-custom {
            border-color: #dc3545 !important;
        }

        .error-message-custom {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
</head>

<body>

    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-register100">
                <form class="login100-form validate-form" method="POST" action="{{ Route('register-clinic') }}" id="clinicRegistrationForm">
                    @csrf
                    <span class="login100-form-title p-b-26">

                        {{ __('Register Clinic') }}
                    </span>

                    <div id="stepper">
                        <!-- Step 1: Clinic Details -->
                        <div class="step step-1">
                            <h4 class="register-step-1">{{ __('Step 1: Clinic Information') }}</h4>
                            <div class="form-group">
                                <label class="form-label required">{{ __('Clinic Name')}}</label>
                                <input type="text" name="clinic_name" class="form-control @error('clinic_name') is-invalid @enderror" value="{{ old('clinic_name') }}" required>
                                @error('clinic_name')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label required">{{ __('Start Date') }}</label>
                                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                                        @error('start_date')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label required">{{ __('specialty') }}</label>
                                        <select name="specialty_id" class="form-control @error('specialty_id') is-invalid @enderror">
                                            <option value="">{{ __('Choose From List') }}</option>
                                            @foreach (App\Models\Specialty::all() as $specialty)
                                            <option value="{{ $specialty->id }}" {{ old('specialty_id') == $specialty->id ? 'selected' : '' }}>{{ $specialty->name_en }}</option>
                                            @endforeach
                                        </select>
                                        @error('specialty_id')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label required">{{ __('Phone') }}</label>
                                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                                        @error('phone')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label required">{{ __('Email') }}</label>
                                        <input type="email" name="clinic_email" class="form-control @error('clinic_email') is-invalid @enderror" value="{{ old('clinic_email') }}" required>
                                        @error('clinic_email')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">{{ __('Governorate') }}</label>
                                        <select name="governorate_id" class="form-control @error('governorate_id') is-invalid @enderror">
                                            <option value="">{{ __('Choose From List') }}</option>
                                            @foreach (App\Models\Governorate::all() as $governorate)
                                            <option value="{{ $governorate->id }}" {{ old('governorate_id') == $governorate->id ? 'selected' : '' }}>{{ $governorate->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('governorate_id')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">{{ __('City') }}</label>
                                        <select name="city_id" class="form-control @error('city_id') is-invalid @enderror">
                                            <option value="">{{ __('Choose From List') }}</option>

                                        </select>
                                        @error('city_id')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">{{ __('Area') }}</label>
                                        <select name="area_id" class="form-control @error('area_id') is-invalid @enderror">
                                            <option value="">{{ __('Choose From List') }}</option>

                                        </select>

                                        @error('area_id')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label required">{{ __('Address') }}</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" ></textarea>
                                @error('address')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ __('Latitude') }}</label>
                                        <input type="text" name="latitude" id="latitude" class="form-control
                                        @error('latitude') is-invalid @enderror" value="{{ old('latitude') }}" required>
                                        @error('latitude')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ __('Longitude') }}</label>
                                        <input type="text" name="longitude" id="longitude" class="form-control @error('longitude') is-invalid @enderror" value="{{ old('longitude') }}" required>
                                        @error('longitude')
                                        <div class="error-message">{{ $message }}</div>
                                        @enderror

                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <input type="text" name="location" id="location">
                                <div id="map" style="height: 400px; width: 100%;"></div>
                            </div>




                            <button type="button" class="btn btn-primary next-step mt-4">{{ __('Next') }}</button>
                        </div>

                        <!-- Step 2: User Details -->
                        <div class="step step-2 d-none">
                            <h4 class="register-step-2">{{ __('Step 2: User Information') }}</h4>
                            <div class="form-group">
                                <label class="form-label required">{{ __('User Name') }}</label>
                                <input type="text" name="user_name" class="form-control @error('user_name') is-invalid @enderror" value="{{ old('user_name') }}" required>
                                @error('user_name')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label required">{{ __('User Email') }}</label>
                                <input type="email" name="user_email" class="form-control @error('user_email') is-invalid @enderror" value="{{ old('user_email') }}" required>
                                @error('user_email')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label required">{{ __('Password') }}</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label required">{{ __('Confirm Password') }}</label>
                                <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" required>
                                @error('password_confirmation')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="button" class="btn btn-secondary prev-step">{{ __('Back') }}</button>
                            <button type="submit" class="btn btn-success">{{ __('Register') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div id="dropDownSelect1"></div>

    @php
    // $api_key = env('GOOGLE_MAP_API_KEY');
    $api_key = 'AIzaSyBUK88jmlcZv3IdJlhp944cJmzkWKelqq4';

    @endphp
    <!--===============================================================================================-->
    <script src="{{asset('login/vendor/jquery/jquery-3.2.1.min.js')}}"></script>
    <!--===============================================================================================-->
    <!-- <script src="{{asset('login/vendor/animsition/js/animsition.min.js')}}"></script> -->
    <!--===============================================================================================-->
    <!-- <script src="{{asset('login/vendor/bootstrap/js/popper.js')}}"></script> -->
    <script src="{{asset('login/vendor/bootstrap/js/bootstrap.min.js')}}"></script>
    <!--===============================================================================================-->
    <script src="{{asset('login/vendor/select2/select2.min.js')}}"></script>
    <!--===============================================================================================-->
    <script src="{{asset('login/vendor/daterangepicker/moment.min.js')}}"></script>
    <script src="{{asset('login/vendor/daterangepicker/daterangepicker.js')}}"></script>
    <!--===============================================================================================-->
    <!-- <script src="{{asset('login/vendor/countdowntime/countdowntime.js')}}"></script> -->
    <!--===============================================================================================-->
    <script src="{{asset('login/js/main.js')}}"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        // Show server-side validation errors as toastr
        @if($errors->any())
        @foreach($errors->all() as $error)
        toastr.error("{{ $error }}");
        @endforeach
        @endif
        @if(session('success'))
        toastr.success("{{ session('success') }}");
        @endif
        @if(session('error'))
        toastr.error("{{ session('error') }}");
        @endif

        $(document).ready(function() {
            $(".next-step").click(function() {

                $(".step-1").addClass("d-none");
                $(".step-2").removeClass("d-none");

                // Get all required fields in step 1
                let requiredFields = $(".step-1").find('input[required], select[required]');
                let isValid = true;
                let firstInvalidField = null;

                // Remove any existing error messages
                $('.error-message-custom').remove();
                $('.is-invalid-custom').removeClass('is-invalid-custom');

                // Check each required field
                requiredFields.each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        // Add error class
                        $(this).addClass('is-invalid-custom');
                        // Add error message
                        let fieldName = $(this).prev('label').text().replace(' *', '');
                        $(this).after('<div class="error-message error-message-custom">' + fieldName + ' is required</div>');

                        // Store the first invalid field
                        if (!firstInvalidField) {
                            firstInvalidField = $(this);
                        }
                    }
                });

                // Special validation for email format
                let emailField = $('input[name="clinic_email"]');
                let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailField.val() && !emailRegex.test(emailField.val())) {
                    isValid = false;
                    emailField.addClass('is-invalid-custom');
                    emailField.after('<div class="error-message error-message-custom">Please enter a valid email address</div>');
                    if (!firstInvalidField) {
                        firstInvalidField = emailField;
                    }
                }

                // Special validation for phone format
                let phoneField = $('input[name="phone"]');
                let phoneRegex = /^01[0-2|5]\d{8}$/; // Matches Egyptian phone numbers like 01064313821

                if (phoneField.val() && !phoneRegex.test(phoneField.val())) {
                    isValid = false;
                    phoneField.addClass('is-invalid-custom');
                    phoneField.after('<div class="error-message error-message-custom">Please enter a valid 11-digit phone number</div>');
                    if (!firstInvalidField) {
                        firstInvalidField = phoneField;
                    }
                }

                if (isValid) {
                    $(".step-1").addClass("d-none");
                    $(".step-2").removeClass("d-none");
                } else {
                    // Scroll to the first invalid field
                    if (firstInvalidField) {
                        $('html, body').animate({
                            scrollTop: firstInvalidField.offset().top - 100
                        }, 500);
                    }
                    // Show error toast
                    toastr.error('Please fill in all required fields correctly before proceeding.');
                }

            });

            $(".prev-step").click(function() {
                $(".step-2").addClass("d-none");
                $(".step-1").removeClass("d-none");
            });

            // Fetch cities based on selected governorate
            $('select[name="governorate_id"]').change(function() {
                let governorateId = $(this).val();
                if (governorateId) {
                    $.ajax({
                        url: "{{ route('cities.by-governorate') }}",
                        type: 'GET',
                        data: {
                            governorate_id: governorateId
                        },
                        success: function(data) {
                            let citySelect = $('select[name="city_id"]');
                            citySelect.empty().append('<option value="">{{ __("Choose From List") }}</option>');
                            $.each(data.data, function(key, value) {
                                citySelect.append('<option value="' + value.id + '">' + value.name + '</option>');
                            });
                        }
                    });
                }
            });

            // Fetch areas based on selected city
            $('select[name="city_id"]').change(function() {
                let cityId = $(this).val();
                if (cityId) {
                    $.ajax({
                        url: "{{ route('areas.by-city') }}",
                        type: 'GET',
                        data: {
                            city_id: cityId
                        },
                        success: function(data) {
                            let areaSelect = $('select[name="area_id"]');
                            areaSelect.empty().append('<option value="">{{ __("Choose From List") }}</option>');
                            $.each(data.data, function(key, value) {
                                areaSelect.append('<option value="' + value.id + '">' + value.name + '</option>');
                            });
                        }
                    });
                }
            });

            // Form submission with AJAX
            $('#clinicRegistrationForm').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log(response);
                        if (response.success) {
                            toastr.success(response.message);
                            $('#clinicRegistrationForm')[0].reset();
                            $(".step-2").addClass("d-none");
                            $(".step-1").removeClass("d-none");
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {

                        if (xhr.status === 422) {
                            console.log(xhr);
                            let errors = xhr.responseJSON.errors;
                            console.log(errors);

                            Object.values(errors).forEach(function(messages) {
                                messages.forEach(function(message) {
                                    toastr.error(message);
                                });
                            });
                        } else {
                            console.log(xhr);
                            toastr.error('Registration failed. Please try again.');
                        }
                    }
                });
            });
        });
    </script>

    @if(!empty($api_key))

    <script>
        function initAutocomplete() {
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat: -33.8688,
                    lng: 151.2195
                },
                zoom: 10,
                mapTypeId: 'roadmap'
            });

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    map.setCenter(initialLocation);
                });
            }


            // Create the search box and link it to the UI element.
            var input = document.getElementById('location');
            var searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

            // Bias the SearchBox results towards current map's viewport.
            map.addListener('bounds_changed', function() {
                searchBox.setBounds(map.getBounds());
            });

            var markers = [];
            // Listen for the event fired when the user selects a prediction and retrieve
            // more details for that place.
            searchBox.addListener('places_changed', function() {
                var places = searchBox.getPlaces();

                if (places.length == 0) {
                    return;
                }

                // Clear out the old markers.
                markers.forEach(function(marker) {
                    marker.setMap(null);
                });
                markers = [];

                // For each place, get the icon, name and location.
                var bounds = new google.maps.LatLngBounds();
                places.forEach(function(place) {
                    if (!place.geometry) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    var icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25)
                    };

                    // Create a marker for each place.
                    markers.push(new google.maps.Marker({
                        map: map,
                        icon: icon,
                        title: place.name,
                        position: place.geometry.location
                    }));

                    //set position field value
                    var lat_long = [place.geometry.location.lat(), place.geometry.location.lng()]
                    $('#position').val(lat_long);

                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
        }

        function initMap(lat, lng) {
            const mapElement = document.getElementById('map');
            const inputElement = document.getElementById('location');

            if (!mapElement || !inputElement) {
                console.warn("Map or input element not found.");
                return;
            }

            // Log the initial latitude and longitude
            console.log(`Initializing map at Latitude: ${lat}, Longitude: ${lng}`);

            const defaultLocation = {
                lat: lat,
                lng: lng
            };

            // Initialize the map with the provided location
            const map = new google.maps.Map(mapElement, {
                zoom: 8,
                center: defaultLocation,
            });

            // Draggable marker at the initial location
            let marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true,
            });

            // Try to set user location from geolocation if available
            // if (navigator.geolocation) {
            //     navigator.geolocation.getCurrentPosition(
            //         (position) => {
            //             const userLocation = {
            //                 lat: position.coords.latitude,
            //                 lng: position.coords.longitude
            //             };

            //             // Update both map center and marker position at once
            //             console.log(`User location found: Latitude: ${userLocation.lat}, Longitude: ${userLocation.lng}`);
            //             map.setCenter(userLocation);
            //             marker.setPosition(userLocation);
            //         },
            //         (error) => {
            //             console.warn("Geolocation permission denied. Using provided location.", error);
            //         },
            //         { enableHighAccuracy: true } // Request high accuracy if available
            //     );
            // } else {
            //     console.warn("Geolocation not supported. Using provided location.");
            // }

            // Update latitude and longitude fields when the marker is dragged
            google.maps.event.addListener(marker, 'position_changed', function() {
                document.getElementById('latitude').value = marker.getPosition().lat();
                document.getElementById('longitude').value = marker.getPosition().lng();
            });

            // Move the marker on map click
            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
            });

            // Setup search box
            const searchBox = new google.maps.places.SearchBox(inputElement);
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(inputElement);

            // Adjust bounds based on search results
            map.addListener('bounds_changed', function() {
                searchBox.setBounds(map.getBounds());
            });

            let markers = [];
            searchBox.addListener('places_changed', function() {
                const places = searchBox.getPlaces();

                if (!places.length) return;

                markers.forEach(marker => marker.setMap(null));
                markers = [];

                const bounds = new google.maps.LatLngBounds();
                places.forEach(place => {
                    if (!place.geometry) return;

                    const newMarker = new google.maps.Marker({
                        map: map,
                        position: place.geometry.location,
                    });
                    markers.push(newMarker);

                    if (place.geometry.viewport) {
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key={{$api_key}}&libraries=places" async defer></script>
    <script type="text/javascript">
        $(document).ready(function(e) {

            // Get the latitude and longitude from the form inputs
            const clientLatitude = parseFloat(document.getElementById('latitude').value) || 30.4669; // Fallback
            const clientLongitude = parseFloat(document.getElementById('longitude').value) || 31.1842; // Fallback

            console.log(`Client Latitude: ${clientLatitude}, Client Longitude: ${clientLongitude}`);
            initMap(clientLatitude, clientLongitude);
        });
    </script>

    @endif


</body>

</html>
