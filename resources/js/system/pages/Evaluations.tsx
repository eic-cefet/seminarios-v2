import { PendingEvaluation, profileApi } from "@shared/api/client";
import { PageTitle } from "@shared/components/PageTitle";
import { getErrorMessage } from "@shared/lib/errors";
import { cn, formatDateTime } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
    Calendar,
    Check,
    Loader2,
    MapPin,
    MessageSquare,
    Star,
} from "lucide-react";
import { useState } from "react";
import { Link } from "react-router-dom";
import { Badge } from "../components/Badge";
import { Layout } from "../components/Layout";
import { ProtectedRoute } from "../components/ProtectedRoute";

export default function Evaluations() {
    return (
        <ProtectedRoute>
            <PageTitle title="Avaliar Seminarios" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Avaliar Seminarios
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Avalie os seminarios que voce participou nos ultimos
                            30 dias
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                    <PendingEvaluationsList />
                </div>
            </Layout>
        </ProtectedRoute>
    );
}

function PendingEvaluationsList() {
    const {
        data,
        isLoading,
        refetch: refetchEvaluations,
    } = useQuery({
        queryKey: ["profile", "pending-evaluations"],
        queryFn: () => profileApi.pendingEvaluations(),
    });

    const evaluations = data?.data ?? [];

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-12">
                <Loader2 className="h-8 w-8 animate-spin text-gray-400" />
            </div>
        );
    }

    if (evaluations.length === 0) {
        return (
            <div className="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center">
                <Star className="mx-auto h-12 w-12 text-gray-300" />
                <h3 className="mt-4 text-lg font-medium text-gray-900">
                    Nenhuma avaliacao pendente
                </h3>
                <p className="mt-2 text-sm text-gray-500">
                    Voce ja avaliou todos os seminarios que participou
                    recentemente.
                </p>
                <Link
                    to="/perfil"
                    className="mt-6 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer"
                >
                    Ir para o perfil
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="rounded-lg border border-gray-200 bg-white overflow-hidden">
                <div className="border-b border-gray-200 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Star className="h-5 w-5 text-gray-400" />
                            <h2 className="text-lg font-semibold text-gray-900">
                                Seminarios para avaliar
                            </h2>
                        </div>
                        <span className="text-sm text-gray-500">
                            {evaluations.length} seminario
                            {evaluations.length !== 1 ? "s" : ""}
                        </span>
                    </div>
                </div>

                <div className="divide-y divide-gray-200">
                    {evaluations.map((evaluation) => (
                        <EvaluationItem
                            key={evaluation.id}
                            evaluation={evaluation}
                            onRated={() => refetchEvaluations()}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

interface EvaluationItemProps {
    evaluation: PendingEvaluation;
    onRated: () => void;
}

function EvaluationItem({ evaluation, onRated }: EvaluationItemProps) {
    const [isExpanded, setIsExpanded] = useState(false);
    const [score, setScore] = useState(0);
    const [hoveredScore, setHoveredScore] = useState(0);
    const [comment, setComment] = useState("");
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);
    const queryClient = useQueryClient();

    const mutation = useMutation({
        mutationFn: () =>
            profileApi.submitRating(evaluation.seminar.id, {
                score,
                comment: comment.trim() || undefined,
            }),
        onSuccess: () => {
            setError(null);
            setSuccess(true);
            analytics.event("evaluation_submit", {
                seminar_id: evaluation.seminar.id,
                score,
            });
            // Invalidate and refetch
            queryClient.invalidateQueries({
                queryKey: ["profile", "pending-evaluations"],
            });
            setTimeout(() => {
                onRated();
            }, 1500);
        },
        onError: (err) => {
            setError(getErrorMessage(err));
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (score === 0) {
            setError("Selecione uma nota de 1 a 5 estrelas");
            return;
        }
        if (score <= 3 && !comment.trim()) {
            setError(
                "Por favor, conte-nos o que podemos melhorar (comentario obrigatorio para notas ate 3)",
            );
            return;
        }
        mutation.mutate();
    };

    const displayScore = hoveredScore || score;

    if (success) {
        return (
            <div className="px-6 py-6 bg-green-50">
                <div className="flex items-center gap-3 text-green-700">
                    <Check className="h-5 w-5" />
                    <span className="font-medium">
                        Avaliacao enviada com sucesso!
                    </span>
                </div>
            </div>
        );
    }

    return (
        <div className="px-6 py-4">
            <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                    <Link
                        to={`/seminario/${evaluation.seminar.slug}`}
                        className="font-medium text-gray-900 hover:text-primary-600 cursor-pointer"
                    >
                        {evaluation.seminar.name}
                    </Link>
                    <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                        {evaluation.seminar.seminar_type && (
                            <Badge variant="default">
                                {evaluation.seminar.seminar_type.name}
                            </Badge>
                        )}
                        {evaluation.seminar.scheduled_at && (
                            <span className="flex items-center gap-1">
                                <Calendar className="h-3.5 w-3.5" />
                                {formatDateTime(evaluation.seminar.scheduled_at)}
                            </span>
                        )}
                        {evaluation.seminar.location && (
                            <span className="flex items-center gap-1">
                                <MapPin className="h-3.5 w-3.5" />
                                {evaluation.seminar.location.name}
                            </span>
                        )}
                    </div>
                </div>
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    className={cn(
                        "shrink-0 rounded-md px-4 py-2 text-sm font-medium transition-colors cursor-pointer",
                        isExpanded
                            ? "bg-gray-100 text-gray-700 hover:bg-gray-200"
                            : "bg-primary-600 text-white hover:bg-primary-700",
                    )}
                >
                    {isExpanded ? "Cancelar" : "Avaliar"}
                </button>
            </div>

            {isExpanded && (
                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    {error && (
                        <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Como voce avalia este seminario?
                        </label>
                        <div className="flex items-center gap-1">
                            {[1, 2, 3, 4, 5].map((value) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => setScore(value)}
                                    onMouseEnter={() => setHoveredScore(value)}
                                    onMouseLeave={() => setHoveredScore(0)}
                                    className="p-1 cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded"
                                >
                                    <Star
                                        className={cn(
                                            "h-8 w-8 transition-colors",
                                            value <= displayScore
                                                ? "fill-yellow-400 text-yellow-400"
                                                : "text-gray-300",
                                        )}
                                    />
                                </button>
                            ))}
                            {score > 0 && (
                                <span className="ml-3 text-sm text-gray-600">
                                    {score === 1 && "Muito ruim"}
                                    {score === 2 && "Ruim"}
                                    {score === 3 && "Regular"}
                                    {score === 4 && "Bom"}
                                    {score === 5 && "Excelente"}
                                </span>
                            )}
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor={`comment-${evaluation.id}`}
                            className="block text-sm font-medium text-gray-700 mb-2"
                        >
                            <MessageSquare className="inline h-4 w-4 mr-1" />
                            Comentario{" "}
                            {score > 0 && score <= 3 ? (
                                <span className="text-red-600">(obrigatorio)</span>
                            ) : (
                                <span className="text-gray-400">(opcional)</span>
                            )}
                        </label>
                        <textarea
                            id={`comment-${evaluation.id}`}
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                            rows={3}
                            placeholder={
                                score > 0 && score <= 3
                                    ? "Conte-nos o que podemos melhorar..."
                                    : "Deixe um comentario sobre o seminario..."
                            }
                            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            maxLength={1000}
                        />
                        <p className="mt-1 text-xs text-gray-400 text-right">
                            {comment.length}/1000
                        </p>
                    </div>

                    <div className="flex justify-end">
                        <button
                            type="submit"
                            disabled={mutation.isPending || score === 0}
                            className={cn(
                                "rounded-md bg-primary-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                                (mutation.isPending || score === 0) &&
                                    "opacity-70 cursor-not-allowed",
                            )}
                        >
                            {mutation.isPending
                                ? "Enviando..."
                                : "Enviar avaliacao"}
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}
