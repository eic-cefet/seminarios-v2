import tsParser from "@typescript-eslint/parser";
import tsPlugin from "@typescript-eslint/eslint-plugin";
import jsxA11y from "eslint-plugin-jsx-a11y";

export default [
    {
        files: ["resources/js/**/*.{ts,tsx,js,jsx}"],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaVersion: "latest",
                sourceType: "module",
                ecmaFeatures: { jsx: true },
            },
        },
        plugins: {
            "@typescript-eslint": tsPlugin,
            "jsx-a11y": jsxA11y,
        },
        rules: {
            ...tsPlugin.configs.recommended.rules,
            ...jsxA11y.configs.recommended.rules,
        },
    },
];
