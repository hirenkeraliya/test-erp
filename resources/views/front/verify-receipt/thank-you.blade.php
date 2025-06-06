<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Improving our services </title>

    <style>
        #theme-color {
            background: {{ $themeColor }};
        }
    </style>

    @vite('resources/js/front/app.js')
</head>

<body>
    <div class="flex overflow-hidden">
        <div class="main-content">
            <div class="intro-y justify-center flex">
                <div class="box p-20 text-white text-center rounded-tl-3xl rounded-br-3xl" id="theme-color">
                    <p class="text-2xl">Submitted. &#9745;</p>
                    <p class="mt-2">Your request was submitted successfully</p>

                    <div class="mt-6">
                        <p class="mt-2">Ensure authenticity and quality by purchasing from our
                            <a href="{{ env('GENUINE_OFFICIAL_ONLINE_STORE_LINK', route('front.genuine_receipt_verification.index')) }}" style="text-decoration: underline;" target="_blank" rel="">official online store</a>
                        </p>
                        <a href="{{ route('front.genuine_receipt_verification.index') }}">
                            <p class="mt-2">&#8617; Go Back</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
