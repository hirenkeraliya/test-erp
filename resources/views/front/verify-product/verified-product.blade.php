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
    @if($isGenuine)
    <div class="flex overflow-hidden">
        <div class="main-content">
            <div class="intro-y justify-center flex">
                <div class="box p-20 text-white text-center rounded-tl-3xl rounded-br-3xl" id="theme-color">
                    <p class="text-2xl">Verified</p>
                    <p class="mt-2">Congratulations, your product is genuine and purchased from an authorized retailer</p>
                    @if ($product->getDiskBasedFirstMediaUrl('thumbnail'))
                        <p class="intro-y justify-center flex">
                            <img
                                class="mx-auto rounded mt-5 mb-5"
                                src="{{ $product->getDiskBasedFirstMediaUrl('thumbnail') }}"
                            >
                        </p>
                    @endif
                    <p>
                        Product Name: {{ $product->name }}
                    </p>
                    <p>
                        Product Code: {{ $product->verification_qr_code }}
                    </p>

                    <div class="mt-6">
                        <a href="{{ route('front.genuine_product_verification.index') }}">
                            <p class="mt-2">&#8617; Go Back</p>
                        </a>
                    </div>
                    <div class="social-share mt-3 {{ $isverifiedImageExist == true ? '' : 'generate-verified-image' }}">
                        @foreach($socialLinks as $key => $link)
                            @php
                                $colors = [
                                    'facebook' => 'btn-primary',
                                    'twitter' => 'btn-info',
                                    'linkedin' => 'btn-primary',
                                    'whatsapp' => 'btn-success text-white',
                                ];
                            @endphp

                            <a href="{{ $link }}" target="_blank" class="btn {{ $colors[$key] ?? 'btn-secondary' }} m-1">
                                <i class="fab fa-{{ $key }} fa-2x"></i> 
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="flex overflow-hidden">
        <div class="rounded main-content">
            <div class="flex justify-center intro-y">
                <div class="p-6 box text-white" id="theme-color">
                    <form action="{{ route('front.genuine_product_verification.update') }}"
                        method="post"
                        id="product-verification-form"
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

                            <h3>
                                This product is not genuine.
                            </h3>
                            <input type="hidden" name="product-verification-id" value="{{ $id ?? null }}">

                            <div class="mt-3">
                                <label class="cursor-pointer select-none text-x" for="remarks">
                                    Please enter your purchased source and other details to improve our services
                                </label>
                                <textarea name="remarks"
                                    class="w-full form-control text-black"
                                    placeholder="Enter your purchased source and other details"
                                    value="{{ old('remarks') }}"
                                    required
                                ></textarea>
                                @error('remarks')
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
    @endif
    <script>
        @if(!$isGenuine)
            document.getElementById('remember-me').addEventListener('change', (event) => {
                if (event.currentTarget.checked) {
                    document.getElementById('submit-button').removeAttribute('disabled');
                    return;
                }

                document.getElementById('submit-button').setAttribute('disabled', true);
            });

            document.getElementById('product-verification-form').addEventListener('submit', (event) => {
                event.preventDefault();

                document.getElementById('submit-button').setAttribute('disabled', true);

                document.getElementById('product-verification-form').submit();
            });
        @else
           document.querySelector('.generate-verified-image')?.addEventListener('click', function handler(event) {
                let anchor = event.target.closest('a');
                if (!anchor) return;

                event.preventDefault();

                let container = document.querySelector('.generate-verified-image');

                if (container.dataset.processing) return;
                container.dataset.processing = 'true';

                fetch("{{ route('front.genuine_product_verification.generate_verified_image') }}", { 
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
                    },
                    body: JSON.stringify({
                        product_id: {{ $product->id }}
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        container.querySelectorAll('a').forEach(socialAnchor => {
                            let currentHref = socialAnchor.href;

                            if (currentHref.includes("linkedin.com")) {
                                socialAnchor.href = "https://www.linkedin.com/sharing/share-offsite/?url=" + encodeURIComponent(data.verifiedImageUrl);
                            } else {
                                socialAnchor.href = currentHref + encodeURIComponent(data.verifiedImageUrl);
                            }
                        });

                        container.removeEventListener('click', handler);
                        container.classList.remove('generate-verified-image');

                        setTimeout(() => {
                            anchor.click();
                        }, 100);
                    }
                })
                .catch(error => console.error("Fetch error:", error));
            });
        @endif
    </script>
</body>

</html>
