<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Improving our services </title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: {{ $themeColor }};
        }
        #theme-color {
            background: {{ $themeColor }};
        }
        .text-white{
            --tw-text-opacity: 1;
            color: white;
        }
        .text-center{
            text-align: center;
        }
        .p-20 {
            padding: 1.25rem;
        }

    </style>
</head>

<body>
    <div class="flex overflow-hidden">
        <div class="main-content">
            <div class="intro-y justify-center flex">
                <div class="box p-20 text-white text-center rounded-tl-3xl rounded-br-3xl" id="theme-color">
                    <p class="text-2xl">Verified</p>
                    <p class="mt-2">Congratulations, your receipt is genuine and purchased from an authorized retailer</p>
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
                </div>
            </div>
        </div>
    </div>
</body>

</html>
