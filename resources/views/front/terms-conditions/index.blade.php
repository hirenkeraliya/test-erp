<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - {{ $companyName }}</title>

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
                <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $companyName }} - Terms & Conditions</h1>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8 max-w-4xl">
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <div class="policy-section">
                    <h2 class="text-xl md:text-2xl font-semibold mb-4 theme-color">Terms & Conditions</h2>
                    <p class="text-gray-700 mb-4">
                        By purchasing from
                        <strong>
                            {{ $companyName }} Store,
                        </strong> you agree to the following:
                    </p>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">Product Descriptions</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>Each listing specifies whether the item is NEW or PRELOVED</li>
                        <li>Preloved items may show signs of use (e.g., fading, minor wear) which we will clearly photograph or mention</li>
                        <li>All items are inspected before sale. Serious defects will be stated, and items with unacceptable damage are never sold</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">Pricing & Payment</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>Prices are in
                            <strong>MYR (Malaysian Ringgit)</strong> and are fixed unless during promotional periods</li>
                        <li>Payments must be made in full before we ship or reserve items</li>
                        <li>Orders made online (via DM, website, or other platforms) must be paid within <strong>24 hours</strong> to avoid cancellation</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">Promotions, Sales & Vouchers</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>Discounts and vouchers cannot be combined unless stated</li>
                        <li>Flash sales, bundle deals, or storewide promos are subject to availability</li>
                        <li>Expired vouchers will not be reissued or extended</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">Store Conduct</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>All customers are expected to treat staff and other patrons with respect. Harassment or abusive behavior will result in a ban from further purchases</li>
                        <li>We reserve the right to cancel or refuse any order at our sole discretion (e.g., suspected fraud, non-payment)</li>
                    </ul>
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