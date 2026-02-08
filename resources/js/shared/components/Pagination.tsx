import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@shared/lib/utils";

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}

export function Pagination({
    currentPage,
    lastPage,
    onPageChange,
}: PaginationProps) {
    if (lastPage <= 1) return null;

    return (
        <div className="flex items-center justify-between px-6 py-3 bg-gray-50">
            <span className="text-sm text-gray-500">
                Página {currentPage} de {lastPage}
            </span>
            <div className="flex items-center gap-2">
                <button
                    onClick={() => onPageChange(Math.max(1, currentPage - 1))}
                    disabled={currentPage === 1}
                    className={cn(
                        "inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors cursor-pointer",
                        currentPage === 1
                            ? "text-gray-300 cursor-not-allowed"
                            : "text-gray-700 hover:bg-gray-50",
                    )}
                >
                    <ChevronLeft className="h-4 w-4" />
                    Anterior
                </button>
                <button
                    onClick={() =>
                        onPageChange(Math.min(lastPage, currentPage + 1))
                    }
                    disabled={currentPage === lastPage}
                    className={cn(
                        "inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors cursor-pointer",
                        currentPage === lastPage
                            ? "text-gray-300 cursor-not-allowed"
                            : "text-gray-700 hover:bg-gray-50",
                    )}
                >
                    Próximo
                    <ChevronRight className="h-4 w-4" />
                </button>
            </div>
        </div>
    );
}
