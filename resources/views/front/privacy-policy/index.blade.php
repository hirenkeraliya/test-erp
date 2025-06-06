<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - {{ $companyName }}</title>

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
                <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $companyName }} - Privacy Policy</h1>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8 max-w-4xl">
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <div class="policy-section">
                    <h2 class="text-xl md:text-2xl font-semibold mb-4 theme-color">Privacy Policy</h2>
                    <p class="text-gray-700 mb-4">
                        We care about your data. Beyond PDPA compliance, here's our simple privacy commitment:
                    </p>
                </div>

                <div class="policy-section">
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>We collect only what we need</li>
                        <li>We don't sell or rent your info</li>
                        <li>We use secure systems to protect your data</li>
                        <li>You control your preferences</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <p class="text-gray-700">
                        We use cookies on our website (if applicable) to help us understand shopping behavior and
                        improve your experience. You can disable cookies via browser settings.
                    </p>
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