<template>
    <label :title="label">
        <svg
            width="20"
            viewBox="0 0 38 38"
            xmlns="http://www.w3.org/2000/svg"
            class="w-8 h-8"
        >
            <defs>
                <linearGradient
                    id="a"
                    x1="8.042%"
                    y1="0%"
                    x2="65.682%"
                    y2="23.865%"
                >
                    <stop
                        stop-color="rgb(var(--color-primary) / var(--tw-bg-opacity))"
                        stop-opacity="0"
                        offset="0%"
                    />
                    <stop
                        stop-color="rgb(var(--color-primary) / var(--tw-bg-opacity))"
                        stop-opacity=".631"
                        offset="63.146%"
                    />
                    <stop
                        stop-color="rgb(var(--color-primary) / var(--tw-bg-opacity))"
                        offset="100%"
                    />
                </linearGradient>
            </defs>
            <g
                fill="none"
                fill-rule="evenodd"
            >
                <g transform="translate(1 1)">
                    <path
                        id="Oval-2"
                        d="M36 18c0-9.94-8.06-18-18-18"
                        stroke="url(#a)"
                        stroke-width="3"
                    >
                        <animateTransform
                            attributeName="transform"
                            type="rotate"
                            from="0 18 18"
                            to="360 18 18"
                            dur="0.9s"
                            repeatCount="indefinite"
                        />
                    </path>
                    <circle
                        fill="rgb(var(--color-primary) / var(--tw-bg-opacity))"
                        cx="36"
                        cy="18"
                        r="1"
                    >
                        <animateTransform
                            attributeName="transform"
                            type="rotate"
                            from="0 18 18"
                            to="360 18 18"
                            dur="0.9s"
                            repeatCount="indefinite"
                        />
                    </circle>
                </g>
            </g>
        </svg>
    </label>
</template>

<script setup>

defineProps({
    label: {
        type: String,
        default: null,
    },
});
</script>
