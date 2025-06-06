<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - {{ $companyName }}</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    @php
        $themeColorValue = $themeColor ?? '#000000';
    @endphp

    <style>
        :root {
            --theme-color: {{ $themeColorValue }};
        }
        .policy-section {
            margin-bottom: 2rem;
        }
        .theme-color {
            color: var(--theme-color);
        }
        .theme-bg {
            background-color: var(--theme-color);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="w-full py-6 shadow-md theme-bg">
            <div class="container mx-auto px-4">
                <h1 class="text-2xl md:text-3xl font-bold text-white">Contact {{ $companyName }}</h1>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8 max-w-4xl">
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <div class="policy-section">
                    <h2 class="text-xl md:text-2xl font-semibold mb-4 theme-color">Get in Touch</h2>
                    <p class="text-gray-700 mb-4">Have questions, feedback, or want to collaborate? We're happy to chat.</p>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">{{ $companyName }} HQ</h2>
                    <div class="text-gray-700 space-y-2">
                        <p><strong class="font-semibold">Address:</strong><br>
                        ({{ $companyName }} HQ) LOT G.07-G.14, ANNEXE A, JAKEL MALL, JALAN MUNSHI ABDULLAH<br>
                        50100, KUALA LUMPUR</p>

                        <p><strong class="font-semibold">Phone / WhatsApp:</strong><br>
                        01129405508 (HQ)<br>
                        0187811920 (WANGSA MAJU)</p>

                        <p><strong class="font-semibold">Email:</strong><br>
                        <a href="mailto:{{ $companyEmailAddress }}" class="theme-color hover:underline">{{ $companyEmailAddress }}</a></p>
                    </div>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">Social Media</h2>
                    <div class="grid md:grid-cols-2 gap-6 text-gray-700">
                        <div>
                            <p class="font-semibold mb-2">Instagram:</p>
                            <ul class="space-y-1">
                                <li><a href="https://instagram.com/sovothrift" target="_blank" class="theme-color hover:underline">@sovothrift</a></li>
                                <li><a href="https://instagram.com/fadhlizaihan.ffez" target="_blank" class="theme-color hover:underline">@fadhlizaihan.ffez</a></li>
                                <li><a href="https://instagram.com/sovothrifthq" target="_blank" class="theme-color hover:underline">@sovothrifthq</a></li>
                            </ul>
                        </div>
                        <div>
                            <p class="font-semibold mb-2">TikTok:</p>
                            <ul class="space-y-1">
                                <li><a href="https://tiktok.com/@sovothrift" target="_blank" class="theme-color hover:underline">@sovothrift</a></li>
                                <li><a href="https://tiktok.com/@papapapalisovo_" target="_blank" class="theme-color hover:underline">@papapapalisovo_</a></li>
                                <li><a href="https://tiktok.com/@sovothriftwm" target="_blank" class="theme-color hover:underline">@sovothriftwm</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">Business Hours</h2>
                    <div class="text-gray-700 space-y-4">
                        <div>
                            <p class="font-semibold">{{ $companyName }} HQ:</p>
                            <p>Monday – Sunday, 10:00 AM – 10:00 PM</p>
                        </div>
                        <div>
                            <p class="font-semibold">{{ $companyName }} WM:</p>
                            <p>Monday -Sunday, 12:00 PM – 3:00 AM</p>
                        </div>
                        <p class="italic mt-4">Reach out to us via Instagram DM or WhatsApp for quicker support. We aim to reply within 24 hours (excluding Sundays & public holidays).</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-100 mt-12">
            <div class="container mx-auto px-4 py-6">
                <p class="text-center text-gray-600 text-sm">
                    &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</body>
</html>