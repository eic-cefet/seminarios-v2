import { Link } from 'react-router-dom';
import { Calendar, Users, BookOpen } from 'lucide-react';
import { cn, formatDateTime, isExpired, containsHTML } from '@shared/lib/utils';
import type { Seminar } from '@shared/types';
import { Badge } from './Badge';

interface SeminarCardProps {
    seminar: Seminar;
    showSubject?: boolean;
    className?: string;
}

export function SeminarCard({ seminar, showSubject = true, className }: SeminarCardProps) {
    const expired = isExpired(seminar.scheduledAt);

    return (
        <Link
            to={`/seminario/${seminar.slug}`}
            className={cn(
                'block rounded-lg border bg-white p-5 shadow-sm transition-all hover:shadow-md',
                expired ? 'border-gray-200 opacity-75' : 'border-gray-200 hover:border-primary-300',
                className
            )}
        >
            <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                        {seminar.seminarType && (
                            <Badge variant={expired ? 'expired' : 'default'}>
                                {seminar.seminarType.name}
                            </Badge>
                        )}
                        {expired && <Badge variant="expired">Encerrado</Badge>}
                    </div>

                    <h3
                        className={cn(
                            'mt-2 text-lg font-semibold line-clamp-2',
                            expired ? 'text-gray-500' : 'text-gray-900'
                        )}
                    >
                        {seminar.name}
                    </h3>

                    {seminar.description && (
                        <p className="mt-1 text-sm text-gray-500 line-clamp-2">
                            {containsHTML(seminar.description)
                                ? 'Clique para ver os detalhes'
                                : seminar.description}
                        </p>
                    )}

                    <div className="mt-4 flex items-center gap-4 text-sm text-gray-500">
                        <span className="flex items-center gap-1">
                            <Calendar className="h-4 w-4" />
                            {formatDateTime(seminar.scheduledAt)}
                        </span>
                        {seminar.registrationsCount !== undefined && (
                            <span className="flex items-center gap-1">
                                <Users className="h-4 w-4" />
                                {seminar.registrationsCount} inscritos
                            </span>
                        )}
                    </div>

                    {showSubject && seminar.subjects && seminar.subjects.length > 0 && (
                        <div className="mt-2 flex items-center gap-1 text-sm text-gray-500">
                            <BookOpen className="h-4 w-4" />
                            {seminar.subjects.map((s) => s.name).join(', ')}
                        </div>
                    )}
                </div>
            </div>
        </Link>
    );
}
