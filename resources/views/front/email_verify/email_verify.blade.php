<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Thank You </title>
    <style>
        /* Your theme/color styles */
        #theme-color {
            background-color: {{ $themeColor }};
        }
    </style>

    @vite('resources/js/front/app.js')
</head>

<body>
     <div class="flex overflow-hidden">
        <div class="main-content">
            <div class="intro-y justify-center flex">
                <div class="box bg-purple-custom p-20 text-white text-center rounded-tl-3xl rounded-br-3xl" id
="theme-color">                    
                    <p class="text-2xl">Email Verified!</p>
                    <p class="mt-2">Your email address was successfully verified</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
