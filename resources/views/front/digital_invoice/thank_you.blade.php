<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Thank You </title>

    @vite('resources/js/front/app.js')
</head>

<body>
     <div class="flex overflow-hidden">
        <div class="main-content">
            <div class="intro-y justify-center flex">
                <div class="box bg-purple-custom p-20 text-white text-center rounded-tl-3xl rounded-br-3xl">
                    <p class="text-2xl">Thank you !</p>
                    @if($isSubmitted)
                        <p class="mt-2">You have already submitted the E-invoice details.</p>
                    @else
                        <p class="mt-2">You have successfully submitted E-invoice details.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>

</html>
