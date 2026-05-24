import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";
import type {
    AdminRatingSentimentsResponse,
    AdminSentimentLabel,
} from "./ratings";

export type AiAction =
    | "format_markdown"
    | "shorten"
    | "explain"
    | "formal"
    | "casual";

export const aiApi = {
    transformText: async (text: string, action: AiAction) => {
        await getCsrfCookie();
        return fetchAdminApi<{ data: { text: string } }>(
            "/ai/transform-text",
            {
                method: "POST",
                body: JSON.stringify({ text, action }),
            },
        );
    },

    suggestMergeName: async (names: string[]) => {
        await getCsrfCookie();
        return fetchAdminApi<{ data: { text: string } }>(
            "/ai/suggest-merge-name",
            {
                method: "POST",
                body: JSON.stringify({ names }),
            },
        );
    },
    suggestSubjectTags: async (subjectNames: string[]) => {
        await getCsrfCookie();
        return fetchAdminApi<{ data: { suggestions: string[] } }>(
            "/ai/suggest-subject-tags",
            {
                method: "POST",
                body: JSON.stringify({ subject_names: subjectNames }),
            },
        );
    },

    ratingSentiments: (params?: {
        page?: number;
        per_page?: number;
        search?: string;
        score?: number;
        sentiment_label?: Exclude<AdminSentimentLabel, null> | "null";
    }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<AdminRatingSentimentsResponse>(
            `/ai/rating-sentiments${qs}`,
        );
    },
};
