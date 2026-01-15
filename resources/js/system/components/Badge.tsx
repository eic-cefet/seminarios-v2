import { cn } from '@shared/lib/utils';

interface BadgeProps {
    variant?: 'default' | 'success' | 'warning' | 'error' | 'expired';
    children: React.ReactNode;
    className?: string;
}

const variantStyles = {
    default: 'bg-gray-100 text-gray-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-yellow-100 text-yellow-700',
    error: 'bg-red-100 text-red-700',
    expired: 'bg-gray-200 text-gray-500',
};

export function Badge({ variant = 'default', children, className }: BadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                variantStyles[variant],
                className
            )}
        >
            {children}
        </span>
    );
}
