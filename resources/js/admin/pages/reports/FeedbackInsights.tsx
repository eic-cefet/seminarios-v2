import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, MessageSquare, Star, AlertTriangle } from "lucide-react";
import { PageTitle } from "@shared/components/PageTitle";
import { Pagination } from "@shared/components/Pagination";
import { formatDateTime } from "@shared/lib/utils";
import {
    aiApi,
    type AdminRating,
    type AdminSentimentLabel,
} from "../../api/adminClient";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";
import { Badge } from "../../components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";

type SentimentFilter = Exclude<AdminSentimentLabel, null> | "all" | "null";
type ScoreFilter = "all" | "1" | "2" | "3" | "4" | "5";

function sentimentCopy(label: AdminSentimentLabel) {
    switch (label) {
        case "positive":
            return { text: "Positivo", variant: "success" as const };
        case "negative":
            return { text: "Negativo", variant: "destructive" as const };
        case "neutral":
            return { text: "Neutro", variant: "secondary" as const };
        case "mixed":
            return { text: "Misto", variant: "warning" as const };
        default:
            return { text: "Sem rótulo", variant: "outline" as const };
    }
}

function RatingRow({ rating }: { rating: AdminRating }) {
    const sentiment = sentimentCopy(rating.sentiment_label ?? null);

    return (
        <TableRow>
            <TableCell className="font-medium">
                {rating.seminar?.name ?? "-"}
            </TableCell>
            <TableCell>{rating.user?.name ?? "-"}</TableCell>
            <TableCell>{rating.score}</TableCell>
            <TableCell className="max-w-xs whitespace-normal break-words text-sm text-muted-foreground">
                {rating.comment?.trim() || "-"}
            </TableCell>
            <TableCell className="max-w-sm whitespace-normal break-words text-sm text-muted-foreground">
                {rating.sentiment?.trim() || "-"}
            </TableCell>
            <TableCell>
                <Badge variant={sentiment.variant}>{sentiment.text}</Badge>
            </TableCell>
            <TableCell>
                {rating.sentiment_analyzed_at
                    ? formatDateTime(rating.sentiment_analyzed_at)
                    : "-"}
            </TableCell>
        </TableRow>
    );
}

export default function FeedbackInsights() {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState("");
    const [scoreFilter, setScoreFilter] = useState<ScoreFilter>("all");
    const [sentimentFilter, setSentimentFilter] =
        useState<SentimentFilter>("all");

    const { data, isLoading, isError } = useQuery({
        queryKey: [
            "admin-rating-sentiments",
            page,
            search,
            scoreFilter,
            sentimentFilter,
        ],
        queryFn: () =>
            aiApi.ratingSentiments({
                page,
                per_page: 10,
                search: search || undefined,
                score:
                    scoreFilter !== "all"
                        ? Number.parseInt(scoreFilter, 10)
                        : undefined,
                sentiment_label:
                    sentimentFilter !== "all" ? sentimentFilter : undefined,
            }),
    });

    const ratings = data?.data ?? [];
    const meta = data?.meta;
    const summary = data?.summary;
    const hasFilters =
        search.length > 0 ||
        scoreFilter !== "all" ||
        sentimentFilter !== "all";

    const resetFilters = () => {
        setSearch("");
        setScoreFilter("all");
        setSentimentFilter("all");
        setPage(1);
    };

    return (
        <div className="space-y-6">
            <PageTitle title="Feedback IA" />

            <div>
                <h1 className="text-2xl font-bold text-foreground">
                    Feedback IA
                </h1>
                <p className="text-muted-foreground">
                    Avaliações analisadas por IA para acompanhar satisfação,
                    críticas e sinais de atenção.
                </p>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium">
                            Avaliações analisadas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {summary?.total_ratings ?? 0}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium">
                            <Star className="h-4 w-4 text-yellow-500" />
                            Média das notas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {summary?.average_score ?? "-"}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium">
                            <AlertTriangle className="h-4 w-4 text-yellow-500" />
                            Notas até 3
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {summary?.low_score_count ?? 0}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_220px_auto]">
                        <div className="space-y-2">
                            <Label htmlFor="feedback-search">Buscar</Label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="feedback-search"
                                    placeholder="Seminário, avaliador, comentário..."
                                    value={search}
                                    onChange={(event) => {
                                        setSearch(event.target.value);
                                        setPage(1);
                                    }}
                                    className="pl-9"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="feedback-score">Nota</Label>
                            <Select
                                value={scoreFilter}
                                onValueChange={(value: ScoreFilter) => {
                                    setScoreFilter(value);
                                    setPage(1);
                                }}
                            >
                                <SelectTrigger id="feedback-score">
                                    <SelectValue placeholder="Todas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas</SelectItem>
                                    <SelectItem value="5">5 estrelas</SelectItem>
                                    <SelectItem value="4">4 estrelas</SelectItem>
                                    <SelectItem value="3">3 estrelas</SelectItem>
                                    <SelectItem value="2">2 estrelas</SelectItem>
                                    <SelectItem value="1">1 estrela</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="feedback-sentiment">
                                Classificação
                            </Label>
                            <Select
                                value={sentimentFilter}
                                onValueChange={(value: SentimentFilter) => {
                                    setSentimentFilter(value);
                                    setPage(1);
                                }}
                            >
                                <SelectTrigger id="feedback-sentiment">
                                    <SelectValue placeholder="Todas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas</SelectItem>
                                    <SelectItem value="positive">
                                        Positivo
                                    </SelectItem>
                                    <SelectItem value="negative">
                                        Negativo
                                    </SelectItem>
                                    <SelectItem value="neutral">
                                        Neutro
                                    </SelectItem>
                                    <SelectItem value="mixed">Misto</SelectItem>
                                    <SelectItem value="null">
                                        Sem rótulo
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex items-end">
                            <button
                                type="button"
                                onClick={resetFilters}
                                disabled={!hasFilters}
                                className="inline-flex h-10 items-center rounded-md border border-border px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Limpar filtros
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Avaliações analisadas
                    </CardTitle>
                </CardHeader>
                <CardContent className="px-0">
                    {isLoading ? (
                        <div className="px-6 py-10 text-center text-muted-foreground">
                            Carregando feedback analisado...
                        </div>
                    ) : isError ? (
                        <div className="px-6 py-10 text-center text-muted-foreground">
                            Erro ao carregar feedback analisado.
                        </div>
                    ) : ratings.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <MessageSquare className="mx-auto h-10 w-10 text-muted-foreground" />
                            <p className="mt-4 text-sm text-muted-foreground">
                                Nenhuma avaliação analisada encontrada para os
                                filtros atuais.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Seminário</TableHead>
                                            <TableHead>Avaliador</TableHead>
                                            <TableHead>Nota</TableHead>
                                            <TableHead>Comentário</TableHead>
                                            <TableHead>Análise IA</TableHead>
                                            <TableHead>Classificação</TableHead>
                                            <TableHead>Analisado em</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {ratings.map((rating) => (
                                            <RatingRow
                                                key={rating.id}
                                                rating={rating}
                                            />
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <Pagination
                                currentPage={meta?.current_page ?? page}
                                lastPage={meta?.last_page ?? 1}
                                onPageChange={setPage}
                            />
                        </>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
