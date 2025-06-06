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
    <title>Add Member</title>
    <style>
        /* Your theme/color styles */
        #theme-color {
            background: {{ $themeColor }};
        }

        /* Loader overlay – hidden by default */
        #loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
        }

        /* === Animation CSS (your provided conveyor belt gift animation) === */
        .scene {
            position: relative;
            width: 300px;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: auto;
            top: 50%;
            transform: translateY(-50%);
        }

        .conveyor {
            position: absolute;
            bottom: 5px;
            width: 100%;
            height: 6px;
            background: #333;
            overflow: hidden;
        }

        .belt {
            position: absolute;
            width: 200%;
            height: 100%;
            background: repeating-linear-gradient(90deg,
                    #555 0px, #555 10px,
                    #333 10px, #333 20px);
            animation: moveBelt 2s linear infinite;
        }

        .gift-box {
            position: absolute;
            width: 50px;
            height: 50px;
            border: 3px solid #ff4747; /* Pink color */
            background: transparent;
            top: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 5px;
        }

        .ribbon-horizontal {
            position: absolute;
            width: 100%;
            height: 3px;
            background: #7c5eff; /* Solid color */
            top: 50%;
            transform: translateY(-50%);
        }

        .ribbon-horizontal::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 3px;
            background: repeating-linear-gradient(
                90deg,
                #fff 0,
                #fff 2px,
                #7c5eff 2px,
                #7c5eff 4px
            ); /* Dotted effect */
            top: 0;
            left: 0;
        }

        .ribbon-vertical {
            position: absolute;
            width: 3px;
            height: 100%;
            background: #7c5eff; /* Solid color */
            left: 50%;
            transform: translateX(-50%);
        }

        .ribbon-vertical::before {
            content: "";
            position: absolute;
            width: 3px;
            height: 100%;
            background: repeating-linear-gradient(
                0deg,
                #fff 0,
                #fff 2px,
                #7c5eff 2px,
                #7c5eff 4px
            ); /* Dotted effect */
            top: 0;
            left: 0;
        }

        .bow {
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bow::before,
        .bow::after {
            content: "";
            width: 14px;
            height: 14px;
            border: 3px solid #7c5eff; /* Solid color */
            border-radius: 50%;
            display: block;
            position: absolute;
        }

        .bow::before {
            left: -1px;
            transform: rotate(-30deg);
        }

        .bow::after {
            right: -1px;
            transform: rotate(30deg);
        }

        .bow::before::before,
        .bow::after::before {
            content: "";
            width: 14px;
            height: 14px;
            border: 3px dotted #fff; /* Dotted effect */
            border-radius: 50%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .knot {
            position: absolute;
            width: 6px;
            height: 6px;
            background: #7c5eff; /* Solid color */
            border-radius: 50%;
            z-index: 2;
        }

        @keyframes moveBelt {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }
    </style>

    @vite('resources/js/front/app.js')
</head>

<body>
    <div class="flex overflow-hidden">
        <div class="rounded main-content">
            <div class="flex justify-center intro-y">
                <div class="p-6 box" id="theme-color">
                    <form action="{{ route('front.mystery_gift.store_member', ['locationId' => $locationId]) }}"
                        method="post"
                        id="member-registration-form"
                    >
                        @csrf

                        <input type="hidden" name="receipt_id" value="{{ $receiptId }}">

                        <div class="grid">
                            <div class="mt-3 mb-4">
                                <a class="flex logo" href="#">
                                    @if ($companyLogo)
                                        <img alt="logo"
                                            class="w-20 mx-auto rounded logo__image"
                                            src="{{ $companyLogo }}"
                                        >
                                    @else
                                        <p class="text-white">
                                            {{ $companyName }}
                                        </p>
                                    @endif
                                </a>
                            </div>

                            <div class="mt-3">
                                <input name="first_name"
                                    type="text"
                                    class="w-full form-control"
                                    placeholder="Enter Full Name"
                                    value="{{ old('first_name') }}"
                                    required
                                >
                                @error('first_name')
                                    <span class="text-red-500">
                                        {{ $message }}
                                    </span
                                @enderror
                            </div>

                            <div class="mt-3">
                                <input name="mobile_number"
                                    type="text"
                                    class="w-full form-control"
                                    placeholder="Mobile Number"
                                    value="{{ old('mobile_number') }}"
                                    required
                                >
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
                                    required
                                >
                                @error('email')
                                    <span class="text-red-500">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <input type="text"
                                    class="form-control"
                                    placeholder="Enter Date Of Birth"
                                    name="date_of_birth"
                                    id="my-date-picker"
                                    class="w-full form-control"
                                    value="{{ old('date_of_birth') }}"
                                    data-date-end-date="0d"
                                >

                                @error('date_of_birth')
                                    <span class="text-red-500">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            @if(!$cookieValue)
                                <div class="mt-3">
                                    {!! captcha_img('flat') !!}
                                </div>

                                <div class="mt-3">
                                    <input name="captcha"
                                        type="text"
                                        class="w-full form-control"
                                        placeholder="Enter Above Code"
                                        required
                                    >
                                    @error('captcha')
                                        <span class="text-red-500">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            @endif

                            <div class="flex items-center mt-5 text-white">
                                <input id="remember-me" type="checkbox" class="mr-2 border form-check-input">
                                <label class="cursor-pointer select-none text-x"
                                    for="remember-me"
                                >
                                    I accept the
                                    <a class="ml-1 text-white underline"
                                        href="{{ config('app.terms_and_condition_page_url') }}"
                                        target="_blank"
                                    >
                                        Terms and Conditions
                                    </a>
                                    and
                                    <a class="ml-1 text-white underline"
                                        href="{{ config('app.pdp_notice_url') }}"
                                        target="_blank"
                                    >
                                        PDP Notice
                                    </a>
                                </label>
                            </div>
                        </div>

                        <div class="mt-8 text-center">
                            <button type="reset"
                                class="mr-1 text-white border-0 btn w-26 button-reset"
                            >
                                Reset
                            </button>

                            <button type="submit"
                                id="submit-button"
                                class="ml-1 text-white border-0 btn w-26 button-submit"
                                disabled
                            >
                                Become A Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="loader">
        <div class="scene">
            <div class="gift-box">
                <div class="ribbon-horizontal"></div>
                <div class="ribbon-vertical"></div>
                <div class="bow">
                    <div class="knot"></div>
                </div>
            </div>
            <div class="conveyor">
                <div class="belt"></div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $(function () {
                $("#my-date-picker").datepicker({
                    format: "yyyy-mm-dd",
                    todayHighlight: true,
                    keyboardNavigation: false,
                    autoclose: true,
                    startView: 2,
                });

                 if (/Mobi|Android/i.test(navigator.userAgent)) {
                    $("#my-date-picker").attr("readonly", true);
                } else {
                    $("#my-date-picker").removeAttr("readonly");
                }
            });
        });

        document.getElementById('remember-me').addEventListener('change', (event) => {
            if (event.currentTarget.checked) {
                document.getElementById('submit-button').removeAttribute('disabled');
                return;
            }

            document.getElementById('submit-button').setAttribute('disabled', true);
        });

        document.getElementById('member-registration-form').addEventListener('submit', (event) => {
            event.preventDefault();
            document.getElementById('submit-button').setAttribute('disabled', true);
            document.getElementById('loader').style.display = 'block';
            document.getElementById('member-registration-form').submit();
        });
    </script>
</body>
</html>
