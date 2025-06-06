<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDPA Policy - {{ $companyName }}</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        #theme-color {
            background-color: {{ $themeColor ?? '#000000' }} !important;
        }
        .policy-section {
            margin-bottom: 2rem;
        }
        .policy-section h2 {
            color: {{ $themeColor ?? '#000000' }} !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header id="theme-color" class="w-full py-6 shadow-md">
            <div class="container mx-auto px-4">
                <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $companyName }} - PDPA Policy</h1>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8 max-w-4xl">
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <div class="policy-section">
                    <h2 class="text-xl md:text-2xl font-semibold mb-4">Personal Data Protection Act (PDPA) Policy</h2>
                    <p class="text-gray-700 mb-4">
                        Your information is safe with us. In compliance with the
                        <strong>Personal Data Protection Act 2010 (PDPA)</strong> of Malaysia, we are
                        committed to safeguarding the privacy of your personal information. When you shop with us,
                        communicate with us, or subscribe to our content, you trust us with your data — and we take
                        that seriously.
                    </p>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3">What We Collect</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>Name (for orders & contact)</li>
                        <li>Email, phone number (for order updates & customer support)</li>
                        <li>Shipping address</li>
                        <li>Order history</li>
                        <li>Any details you voluntarily provide (e.g., during contests, surveys, or inquiries)</li>
                    </ul>
                    <p class="text-gray-700 mt-3">
                        We <strong>do not</strong> store your full payment details — payments are processed securely via trusted
                        payment gateways.
                    </p>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3">Why We Collect</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>To process and ship your orders</li>
                        <li>To verify and respond to inquiries</li>
                        <li>To send updates on your purchase or delivery</li>
                        <li>For limited marketing use (only if you've opted in)</li>
                        <li>For legal and regulatory purposes, including fraud prevention</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3">Who We Share With</h2>
                    <p class="text-gray-700 mb-3">
                        We may share data (only as needed) with:
                    </p>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>Delivery/courier service providers (e.g., J&T, PosLaju)</li>
                        <li>Payment gateway partners (e.g., ToyyibPay, Stripe)</li>
                        <li>Legal authorities (if required under Malaysian law)</li>
                    </ul>
                    <p class="text-gray-700 mt-3">
                        Your data is <strong>never sold</strong> or misused. We implement reasonable security practices, including
                        encryption and staff data handling protocols.
                    </p>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3">Your Rights</h2>
                    <p class="text-gray-700 mb-3">
                        Under Malaysian PDPA, you may:
                    </p>
                    <ul class="list-disc pl-5 text-gray-700 space-y-2">
                        <li>Request to view your personal data</li>
                        <li>Ask for corrections if your info is inaccurate</li>
                        <li>Withdraw consent to use your data (if not essential to service)</li>
                        <li>Request deletion of your data (where allowed)</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2 class="text-lg md:text-xl font-semibold mb-3">Contact Us</h2>
                    <p class="text-gray-700">
                        Email us at {{ $companyEmailAddress }} to make a request. We'll respond within 7 working days.
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