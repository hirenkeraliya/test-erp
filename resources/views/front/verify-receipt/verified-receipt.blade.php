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
        <div class="main-content">
            <div class="intro-y justify-center flex">
                <div class="box p-20 text-white text-center rounded-tl-3xl rounded-br-3xl" id="theme-color">
                    <p class="text-2xl">Verified</p>
                    <p class="mt-2">Congratulations, your receipt is genuine and purchased from an authorized retailer</p>
                    <p>
                        Receipt Number: {{ $sale->offline_sale_id }}
                    </p>

                    <table class="table table-bordered">
                        <tr>
                            <td class="text-left">Image</td>
                            <td class="text-left">Name</td>
                            <td class="text-right">Quantity</td>
                        </tr>
                        @foreach ($saleItems as $saleItem)
                            <tr>
                                <td class="text-left">
                                    <img src="{{ $saleItem['thumbnail'] }}"  alt="{{ $saleItem['product'] }}" style="width: 100px; height: 100px; object-fit: cover;">
                                </td>
                                <td class="text-left">
                                    {{ $saleItem['product'] }}
                                </td>
                                <td class="text-right">
                                    {{ $saleItem['quantity'] }}
                                </td>
                            </tr>
                        @endforeach
                    </table>

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
    <script>
        document.getElementById('remember-me').addEventListener('change', (event) => {
            if (event.currentTarget.checked) {
                document.getElementById('submit-button').removeAttribute('disabled');
                return;
            }

            document.getElementById('submit-button').setAttribute('disabled', true);
        });

        document.getElementById('receipt-verification-form').addEventListener('submit', (event) => {
            event.preventDefault();

            document.getElementById('submit-button').setAttribute('disabled', true);

            document.getElementById('receipt-verification-form').submit();
        });
    </script>
</body>

</html>
