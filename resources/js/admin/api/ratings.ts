export type AdminSentimentLabel =
    | "positive"
    | "negative"
    | "neutral"
    | "mixed"
    | null;

export interface AdminRating {
    id: number;
    score: number;
    comment?: string | null;
    sentiment?: string | null;
    sentiment_label?: AdminSentimentLabel;
    sentiment_analyzed_at?: string | null;
    user?: { id: number; name: string };
    seminar?: { id: number; name: string; slug: string };
    created_at: string;
    updated_at: string;
}

export interface AdminRatingSentimentsResponse {
    data: AdminRating[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total_ratings: number;
        average_score: number | null;
        low_score_count: number;
    };
}
