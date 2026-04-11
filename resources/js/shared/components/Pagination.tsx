import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@shared/lib/utils";

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}

const buttonBase =
    "inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors cursor-pointer";

export function Pagination({
    currentPage,
    lastPage,
    onPageChange,
}: PaginationProps) {
    if (lastPage <= 1) return null;

    const isFirst = currentPage === 1;
    const isLast = currentPage === lastPage;

    return (
        <nav
            aria-label="Paginação"
            className="flex items-center justify-between px-6 py-3 bg-gray-50"
        >
            <span className="text-sm text-gray-500" aria-live="polite">
                Página {currentPage} de {lastPage}
            </span>
            <div className="flex items-center gap-2">
                <button
                    onClick={() => onPageChange(currentPage - 1)}
                    disabled={isFirst}
                    aria-label="Página anterior"
                    className={cn(
                        buttonBase,
                        isFirst
                            ? "text-gray-300 cursor-not-allowed"
                            : "text-gray-700 hover:bg-gray-50",
                    )}
                >
                    <ChevronLeft className="h-4 w-4" />
                    Anterior
                </button>
                <button
                    onClick={() => onPageChange(currentPage + 1)}
                    disabled={isLast}
                    aria-label="Próxima página"
                    className={cn(
                        buttonBase,
                        isLast
                            ? "text-gray-300 cursor-not-allowed"
                            : "text-gray-700 hover:bg-gray-50",
                    )}
                >
                    Próximo
                    <ChevronRight className="h-4 w-4" />
                </button>
            </div>
        </nav>
    );
}
