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
                    <form action="{{ route('front.member.member_add_store', ['store' => $locationId]) }}"
                        method="post"
                        id="member-registration-form"
                    >
                        @csrf
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
                                    </span>
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

            document.getElementById('member-registration-form').submit();
        });
	</script>
</body>
</html>
