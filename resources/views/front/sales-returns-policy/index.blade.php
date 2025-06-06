<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales, Returns & Exchanges Policy - {{ $companyName }}</title>

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
                <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $companyName }} - Sales, Returns & Exchanges Policy</h1>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8 max-w-4xl">
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <div class="policy-section">
                    <h2 class="text-xl md:text-2xl font-semibold mb-4 theme-color">Sales, Returns & Exchanges Policy</h2>
                    <p class="text-gray-700 font-semibold mb-2">Simple. Transparent. Fair.</p>
                    <p class="text-gray-700 mb-4">
                        We want you to love every purchase, but we know that sometimes things go wrong. That's why
                        we offer returns/exchanges under fair and reasonable conditions in accordance with
                        <strong>
                            Malaysian Consumer Protection Act 1999 (CPA).
                        </strong>
                    </p>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">All Sales Are Final — Except When:</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>You received the <strong>wrong item</strong></li>
                        <li>You received a product with a <strong>major defect</strong> that was not listed</li>
                        <li>The item was <strong>damaged in transit</strong> and you reported it within <strong>3 days</strong></li>
                    </ul>
                    <p class="text-gray-700 mt-3">
                        Please take clear photos of the issue and contact us via WhatsApp or email immediately.
                    </p>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">What's Not Returnable</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>Preloved items sold as-is (unless the issue is undisclosed damage)</li>
                        <li>Sale/Clearance items marked as non-returnable</li>
                        <li>Items damaged due to customer handling (e.g., worn, washed, or stained)</li>
                        <li>Accessories, innerwear, hygiene-related items</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3 theme-color">Return Process (If Approved)</h2>
                    <ol class="list-decimal pl-5 text-gray-700 space-y-2 mb-4">
                        <li>Contact us with your order number and issue within <strong>3 days</strong></li>
                        <li>Send back the item within <strong>7 working days</strong> of approval</li>
                        <li>Use a trackable delivery service to avoid losses</li>
                        <li>Once received and inspected, we'll offer you:</li>
                    </ol>
                    <ul class="list-disc pl-8 text-gray-700 space-y-2 mb-4">
                        <li>A same-item exchange (if available), OR</li>
                        <li>Store credit (valid for <strong>3 months</strong>)</li>
                    </ul>
                    <p class="text-gray-700">
                        We do not offer cash refunds unless we are unable to fulfill your original order due to stock
                        issues or errors.
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