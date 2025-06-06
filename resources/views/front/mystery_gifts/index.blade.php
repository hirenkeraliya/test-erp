<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mystery Gift</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        .animate-ping {
            animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        @keyframes pulse {
            50% {
                opacity: .5;
            }
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(-25%);
                animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
            }
            50% {
                transform: translateY(0);
                animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
            }
        }
        .animate-bounce {
            animation: bounce 1s infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-purple-50 to-blue-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
        <div class="p-8">
            @if($isActive)
                <div class="flex justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-center text-gray-900 mb-8">
                    Mystery Gift Awaits You!
                </h1>

                <div id="error-container" class="mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm hidden">
                    <ul class="list-disc pl-4">
                    </ul>
                </div>

                <div id="step-1" class="step-container">
                    <form id="receipt-form" class="space-y-6">
                        <div>
                            <label for="receipt" class="block text-sm font-medium text-gray-700 mb-1">
                                Enter Your Receipt Number
                            </label>
                            <input
                                type="text"
                                id="receipt"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="e.g., RCT-12345"
                                required
                            />
                        </div>
                        <button
                            type="submit"
                            data-original-text="Verify Receipt"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50"
                        >
                            Verify Receipt
                        </button>
                    </form>
                </div>

                <div id="step-2" class="step-container">
                    <form id="customer-form" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name
                            </label>
                            <input
                                type="text"
                                id="name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="John Doe"
                                required
                            />
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <input
                                type="email"
                                id="email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="john@example.com"
                                required
                            />
                        </div>
                        <div>
                            <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">
                                Mobile Number
                            </label>
                            <input
                                type="tel"
                                id="mobile"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="(123) 456-7890"
                                required
                            />
                        </div>

                        <div class="flex space-x-4">
                            <button
                                type="button"
                                id="back-button"
                                class="flex-1 py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                            >
                                Back
                            </button>
                            <button
                                type="submit"
                                data-original-text="Submit"
                                class="flex-1 flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50"
                            >
                                Submit
                            </button>
                        </div>
                    </form>
                </div>

                <div id="step-3" class="step-container">
                    <div id="animation-container" class="flex flex-col items-center justify-center py-10">
                        <div class="relative w-48 h-48">
                            <div class="absolute inset-0 bg-purple-100 rounded-full animate-ping opacity-75"></div>
                            <div class="relative flex items-center justify-center w-48 h-48 bg-purple-500 rounded-full animate-pulse">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-white animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-8 text-lg font-medium text-gray-700">
                            Preparing your mystery gift...
                        </p>
                    </div>
                </div>

                <div id="step-4" class="step-container">
                    <div class="flex flex-col items-center">
                        <div class="w-full max-w-sm bg-gradient-to-r from-purple-400 via-pink-500 to-red-500 rounded-lg p-1">
                            <div class="bg-white rounded-md p-6 text-center" id="reward-container">
                                <!-- Reward content will be inserted here by JavaScript -->
                            </div>
                        </div>

                        <button
                            id="start-over"
                            class="mt-8 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-purple-600 bg-purple-100 hover:bg-purple-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                        >
                            Start Over
                        </button>
                    </div>
                </div>
            @else
                <div class="text-center">
                    <div class="flex justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Offer Closed</h2>
                    <p class="text-gray-600 mb-6">
                        We're sorry, but this mystery gift promotion has ended. Please check back later for more exciting offers!
                    </p>
                    <p class="text-sm text-gray-500">
                        For more information about our current promotions, please visit our main page.
                    </p>
                </div>
            @endif
        </div>
    </div>
</body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const errorContainer = document.getElementById('error-container');
    const steps = ['step-1', 'step-2', 'step-3', 'step-4'];
    let currentStep = 1;
    errorContainer.style.display = 'none';

    function showError(message) {
        const errorList = errorContainer.querySelector('ul');
        errorList.innerHTML = typeof message === 'string' ? `<li>${message}</li>` : '';
        errorContainer.style.display = 'block';
        setTimeout(() => {
            errorContainer.style.display = 'none';
        }, 3000);
    }

    function clearErrors() {
        const errorList = errorContainer.querySelector('ul');
        errorList.innerHTML = '';
        errorContainer.style.display = 'none';
    }

    function showStep(stepNumber) {
        steps.forEach((step, index) => {
            document.getElementById(step).style.display = index + 1 === stepNumber ? 'block' : 'none';
        });
        currentStep = stepNumber;
    }

    function setLoading(button, isLoading) {
        const originalText = button.dataset.originalText;
        button.disabled = isLoading;
        button.innerHTML = isLoading ?
            '<svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...'
            : originalText;
    }

    showStep(1);

    document.getElementById('receipt-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const button = this.querySelector('button[type="submit"]');
        clearErrors();
        setLoading(button, true);

        try {
            const response = await axios.post("{{route('front.mystery_gift.verify_receipt')}}", {
                receipt: document.getElementById('receipt').value
            });

            if (response.data.success === true) {
                if (response.data.hasMember) {
                    showStep(3);
                    setTimeout(() => getReward(), 2000);
                } else {
                    showStep(2);
                }
            } else {
                showError(response.data.message);
            }
        } catch (error) {
            showError('An error occurred. Please try again.');
        }
        setLoading(button, false);
    });

    document.getElementById('customer-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const button = this.querySelector('button[type="submit"]');
        clearErrors();
        setLoading(button, true);

        try {
            const response = await axios.post("{{route('front.mystery_gift.register_member')}}", {
                first_name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                mobile_number: document.getElementById('mobile').value,
                receipt: document.getElementById('receipt').value
            });

            if (response.data.success === true) {
                showStep(3);
                setTimeout(() => getReward(), 2000);
            } else {
                showError(response.data.message);
            }
        } catch (error) {
            if (error.response && error.response.status === 422) {
                const errors = error.response.data.errors;
                const errorMessages = Object.values(errors).flat();
                const errorContainer = document.getElementById('error-container');
                const errorList = errorContainer.querySelector('ul');
                errorList.innerHTML = errorMessages.map(msg => `<li>${msg}</li>`).join('');
                errorContainer.style.display = 'block';
            } else {
                showError('An error occurred. Please try again.');
            }
        }
        setLoading(button, false);
    });

    async function getReward() {
        clearErrors();
        try {
            const response = await axios.post("{{route('front.mystery_gift.get_reward')}}", {
                receipt: document.getElementById('receipt').value
            });

            if (response.data.success === true) {
                const reward = response.data.reward;
                const rewardContainer = document.getElementById('reward-container');

                let html = '';
                html = `
                    <h3 class="text-xl font-bold mb-4">Congratulations!</h3>
                    <p class="text-lg mb-4">You've won:</p>
                    <h4 class="text-2xl font-bold text-purple-600 mb-4">${reward.value}</h4>
                `;

                if (reward.image) {
                    html += `
                        <img src="${reward.image}" alt="${reward.value}" class="w-32 h-32 mx-auto mb-4 rounded-lg">
                    `;
                }

                html += `
                    <img src="data:image/png;base64,${reward.bar_code}" alt="Barcode" class="mx-auto mb-4">
                    <p class="text-sm text-gray-600">Code: ${reward.coupon_code}</p>
                `

                rewardContainer.innerHTML = html;
                showStep(4);
            } else {
                const rewardContainer = document.getElementById('reward-container');

                rewardContainer.innerHTML = `
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h3 class="text-xl font-bold text-red-600 mb-2">Oops!</h3>
                        <p class="text-gray-600 mb-4">${response.data.message}</p>
                    </div>
                `;
                showStep(4);
            }
        } catch (error) {
            showError('Failed to get reward. Please try again.');
            showStep(1);
        }
    }

    document.getElementById('back-button').addEventListener('click', () => showStep(1));
    document.getElementById('start-over').addEventListener('click', () => {
        document.getElementById('receipt-form').reset();
        document.getElementById('customer-form').reset();
        showStep(1);
    });
});
</script>
</html>