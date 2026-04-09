import { Check } from "lucide-react";

export function SuccessAlert({ message }: { message: string }) {
    return (
        <div className="mb-4 flex items-center gap-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
            <Check className="h-4 w-4" />
            {message}
        </div>
    );
}

export function ErrorAlert({ message }: { message: string }) {
    return (
        <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
            {message}
        </div>
    );
}
