<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Login </title>

    @vite('resources/js/admin/app.js')
</head>

<body>
    <div class="flex px-0 md:px-2">
        <div class="flex items-center justify-center h-screen md:h-auto my-auto py-8 px-5 w-full bg-slate-200 rounded">
            <div class="w-full relative grid gap-y-10 gap-x-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <a href="{{ route('admin.login') }}">
                        <button class="relative flex items-center flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md w-full">
                            <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-primary to-primary text-white shadow-indigo-500/40 shadow-lg absolute -mt-4 grid h-12 w-12 place-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </div>
                            <div class="pt-16 pb-14 p-4 text-center">
                                <h4 class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900">
                                    Admin
                                </h4>
                            </div>
                        </button>
                    </a>
                </div>

                <div>
                    <a href="{{ route('super_admin.login') }}">
                        <button class="relative flex items-center flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md w-full">
                            <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-primary to-primary text-white shadow-indigo-500/40 shadow-lg absolute -mt-4 grid h-12 w-12 place-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </div>
                            <div class="pt-16 pb-14 p-4 text-center">
                                <h4 class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900">
                                    Super Admin
                                </h4>
                            </div>
                        </button>
                    </a>
                </div>

                <div>
                    <a href="{{ route('store_manager.login') }}">
                        <button class="relative flex items-center flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md w-full">
                            <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-primary to-primary text-white shadow-indigo-500/40 shadow-lg absolute -mt-4 grid h-12 w-12 place-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                                </svg>

                            </div>
                            <div class="pt-16 pb-14 p-4 text-center">
                                <h4 class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900">
                                    Store Manager
                                </h4>
                            </div>
                        </button>
                    </a>
                </div>

                <div>
                    <a href="{{ route('warehouse_manager.login') }}">
                        <button class="relative flex items-center flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md w-full">
                            <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-primary to-primary text-white shadow-indigo-500/40 shadow-lg absolute -mt-4 grid h-12 w-12 place-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819" />
                                </svg>

                            </div>
                            <div class="pt-16 pb-14 p-4 text-center">
                                <h4 class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900">
                                    Warehouse Manager
                                </h4>
                            </div>
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
