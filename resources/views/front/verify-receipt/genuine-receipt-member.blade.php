<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title> Improving our services </title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        #theme-color {
            background: {{ $themeColor }};
        }
        .social-share .btn.btn-info {
            background-color: #1DA1F2 !important;
            border-color: #1DA1F2 !important;
        }
    </style>

    @vite('resources/js/front/app.js')
</head>

<body>
    <div class="flex overflow-hidden">
        <div class="rounded main-content">
            <div class="flex justify-center intro-y">
                <div class="p-6 box text-white" id="theme-color">
                    <form action="{{ route('front.genuine_receipt_verification.add_genuine_receipt_member') }}"
                        method="post"
                        id="receipt-verification-form"
                    >
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
                                    class="w-full form-control text-black"
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
                                    class="w-full form-control text-black"
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
                                    class="w-full form-control text-black"
                                    placeholder="Enter Email"
                                    value="{{ old('email') }}"
                                    required>
                                @error('email')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>

                            <input type="hidden" name="receipt_number" value="{{ $receiptNumber }}">

                            @if(!isset($cookieValue))
                            <div class="mt-3">
                                {!! captcha_img('flat') !!}
                            </div>

                            <div class="mt-3">
                                <input name="captcha"
                                    type="text"
                                    class="w-full form-control text-black"
                                    placeholder="Enter Above Code"
                                    required>
                                @error('captcha')
                                <span class="text-red-500">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>
                            @endif

                            <div class="flex items-center mt-5">
                                <input id="remember-me" type="checkbox" class="mr-2 border form-check-input">
                                <label class="cursor-pointer select-none text-x"
                                    for="remember-me">
                                    I accept the
                                    <a class="ml-1 underline"
                                        href="{{ config('app.terms_and_condition_page_url') }}"
                                        target="_blank">
                                        Terms and Conditions
                                    </a>
                                    and
                                    <a class="ml-1 underline"
                                        href="{{ config('app.pdp_notice_url') }}"
                                        target="_blank">
                                        PDP Notice
                                    </a>
                                </label>
                            </div>
                        </div>

                        <div class="mt-8 text-center">
                            <button type="reset"
                                class="mr-1 text-white border-0 btn w-26 button-reset">
                                Reset
                            </button>

                            <button type="submit"
                                id="submit-button"
                                class="ml-1 text-white border-0 btn w-26 button-submit"
                                disabled>
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('remember-me').addEventListener('change', (event) => {
            if (event.currentTarget.checked) {
                document.getElementById('submit-button').removeAttribute('disabled');
                return;
            }

            document.getElementById('submit-button').setAttribute('disabled', true);
        });
    </script>
</body>

</html>
