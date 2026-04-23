import tsParser from "@typescript-eslint/parser";
import jsxA11y from "eslint-plugin-jsx-a11y";

export default [
    {
        files: [
            "resources/js/system/**/*.{ts,tsx,js,jsx}",
            "resources/js/shared/**/*.{ts,tsx,js,jsx}",
        ],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaVersion: "latest",
                sourceType: "module",
                ecmaFeatures: { jsx: true },
            },
        },
        plugins: {
            "jsx-a11y": jsxA11y,
        },
        rules: {
            ...jsxA11y.configs.recommended.rules,
        },
    },
];
