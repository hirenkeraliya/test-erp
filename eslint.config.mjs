import globals from "globals";
import pluginJs from "@eslint/js";
import pluginVue from "eslint-plugin-vue";

export default [
    pluginJs.configs.recommended,
    ...pluginVue.configs["flat/recommended"],
    {
        ignores: [
            "vendor/**/**",
            "public/vendor/**/**",
            "public/build/**/**",
            "**/node_modules/**",
            "**/*.config.js",
            "**/*.config.mjs",
            "resources/js/common/vendor/**",
        ],
    },
    {
        files: [
            "resources/js/**/*.js",
            "resources/js/**/*.vue",
        ],
    },
    {
        languageOptions: {
            ecmaVersion: 2021,
            sourceType: "module",
            globals: {
                ...globals.browser,
                defineProps: "readonly",
                defineEmits: "readonly",
                tailwind: true,
                es2021: true
            },
        },
        plugins: {
            vue: pluginVue
        },
        rules: {
            "indent": ["error", 4, { "SwitchCase": 1 }],
            "vue/html-indent": ["error", 4],
            "vue/multi-word-component-names": "off",
            "vue/one-component-per-file": "off",
            "comma-dangle": "off",
            "semi": [1, "always"],
            "no-new": 0,
            "vue/no-v-html": "off",
            "no-console": "error",
            "no-magic-numbers": ["error", { "ignore": [0, 1] }],
        }
    },
];
