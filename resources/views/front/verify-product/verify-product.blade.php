<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <style>
        #theme-color {
            background: {{ $themeColor }};
        }
    </style>

    @vite('resources/js/front/app.js')
</head>

<body>
    <div class="flex overflow-hidden">
        <div class="rounded main-content">
            <div class="flex justify-center intro-y">
                <div class="p-6 box" id="theme-color">
                    <form action="{{ route('front.genuine_product_verification.store') }}"
                        method="post"
                        id="product-verification-form">
                        @csrf
                        <div class="grid">
                            <div class="mt-3 mb-4">
                                <a class="flex logo" href="#">
                                    @if (isset($companyLogo))
                                    <img alt="logo"
                                        class="w-20 mx-auto rounded logo__image"
                                        src="{{ $companyLogo }}">
                                    @else
                                    <p class="text-dark">
                                        {{ config('app.name') }}
                                    </p>
                                    @endif
                                </a>
                            </div>

                            <div class="mt-3">
                                <input name="name"
                                    type="text"
                                    class="w-full form-control"
                                    placeholder="Enter Full Name"
                                    value="{{ old('name') }}"
                                    required>
                                @error('name')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <input name="mobile_number"
                                    type="text"
                                    class="w-full form-control"
                                    placeholder="Mobile Number"
                                    value="{{ old('mobile_number') }}"
                                    required>
                                @error('mobile_number')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <input name="email"
                                    type="email"
                                    class="w-full form-control"
                                    placeholder="Enter Email"
                                    value="{{ old('email') }}"
                                    required>
                                @error('email')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <input name="receipt_number"
                                    type="text"
                                    class="w-full form-control"
                                    placeholder="Enter Receipt Number"
                                    value="{{ old('receipt_number') }}"
                                    required>
                                @error('receipt_number')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <input name="qr_code"
                                    type="text"
                                    class="w-full form-control"
                                    placeholder="Enter QR Code"
                                    value="{{ old('qr_code') }}"
                                    required>
                                @error('qr_code')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>

                            @if(!isset($cookieValue))
                            <div class="mt-3">
                                {!! captcha_img('flat') !!}
                            </div>

                            <div class="mt-3">
                                <input name="captcha"
                                    type="text"
                                    class="w-full form-control"
                                    placeholder="Enter Above Code"
                                    required>
                                @error('captcha')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>
                            @endif
                        </div>

                        <div class="mt-8 text-center">
                            <button type="reset"
                                class="mr-1 text-white border-0 btn w-26 button-reset">
                                Reset
                            </button>

                            <button type="submit"
                                id="submit-button"
                                class="ml-1 text-white border-0 btn w-26 button-submit">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('product-verification-form').addEventListener('submit', (event) => {
            event.preventDefault();

            document.getElementById('submit-button').setAttribute('disabled', true);

            document.getElementById('product-verification-form').submit();
        });
    </script>
</body>

</html>
