import { useQuery, useMutation } from "@tanstack/react-query";
import { CheckCircle, XCircle, Loader2 } from "lucide-react";
import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { useAuth } from "@shared/contexts/AuthContext";
import { LoginModal } from "../components/LoginModal";

const API_BASE = app.API_URL;

interface PresenceResponse {
    data?: {
        seminar: {
            id: number;
            name: string;
            scheduled_at: string;
        };
        is_valid: boolean;
        expires_at: string;
    };
    message?: string;
    is_valid?: boolean;
    is_expired?: boolean;
    is_active?: boolean;
}

export default function Presence() {
    const { uuid } = useParams<{ uuid: string }>();
    const { user } = useAuth();
    const [showLoginModal, setShowLoginModal] = useState(false);
    const [registrationAttempted, setRegistrationAttempted] = useState(false);

    // Check if presence link is valid
    const { data: linkData, isLoading: isCheckingLink, error: linkError } = useQuery({
        queryKey: ["presence-link", uuid],
        queryFn: async () => {
            const response = await fetch(`${API_BASE}/presence/${uuid}`, {
                headers: {
                    Accept: "application/json",
                },
                credentials: "same-origin",
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Link inválido");
            }

            return response.json() as Promise<PresenceResponse>;
        },
        enabled: !!uuid,
    });

    // Register presence mutation
    const registerMutation = useMutation({
        mutationFn: async () => {
            const response = await fetch(`${API_BASE}/presence/${uuid}/register`, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                credentials: "same-origin",
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Erro ao registrar presença");
            }

            return response.json();
        },
    });

    // Auto-register when user is authenticated and link is valid
    useEffect(() => {
        if (
            user &&
            linkData?.data?.is_valid &&
            !registrationAttempted &&
            !registerMutation.isSuccess &&
            !registerMutation.isError
        ) {
            setRegistrationAttempted(true);
            registerMutation.mutate();
        }
    }, [user, linkData, registrationAttempted, registerMutation]);

    // Show login modal if not authenticated
    useEffect(() => {
        if (!user && linkData?.data?.is_valid && !showLoginModal) {
            setShowLoginModal(true);
        }
    }, [user, linkData, showLoginModal]);

    if (isCheckingLink) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <div className="text-center space-y-4">
                    <Loader2 className="h-12 w-12 animate-spin mx-auto text-primary" />
                    <p className="text-muted-foreground">Verificando link...</p>
                </div>
            </div>
        );
    }

    if (linkError || !linkData?.data?.is_valid) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <div className="max-w-md w-full p-8 space-y-4 text-center">
                    <XCircle className="h-16 w-16 mx-auto text-red-500" />
                    <h1 className="text-2xl font-bold text-foreground">
                        Link Inválido
                    </h1>
                    <p className="text-muted-foreground">
                        {linkError instanceof Error
                            ? linkError.message
                            : "Este link de presença não é válido ou expirou."}
                    </p>
                </div>
            </div>
        );
    }

    if (!user) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <div className="max-w-md w-full p-8 space-y-4 text-center">
                    <h1 className="text-2xl font-bold text-foreground">
                        Registrar Presença
                    </h1>
                    <p className="text-muted-foreground">
                        Seminário: <strong>{linkData.data.seminar.name}</strong>
                    </p>
                    <p className="text-muted-foreground">
                        Você precisa estar autenticado para registrar presença.
                    </p>
                    <LoginModal
                        open={showLoginModal}
                        onOpenChange={setShowLoginModal}
                    />
                </div>
            </div>
        );
    }

    if (registerMutation.isPending) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <div className="text-center space-y-4">
                    <Loader2 className="h-12 w-12 animate-spin mx-auto text-primary" />
                    <p className="text-muted-foreground">Registrando presença...</p>
                </div>
            </div>
        );
    }

    if (registerMutation.isError) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <div className="max-w-md w-full p-8 space-y-4 text-center">
                    <XCircle className="h-16 w-16 mx-auto text-red-500" />
                    <h1 className="text-2xl font-bold text-foreground">Erro</h1>
                    <p className="text-muted-foreground">
                        {registerMutation.error instanceof Error
                            ? registerMutation.error.message
                            : "Não foi possível registrar sua presença."}
                    </p>
                </div>
            </div>
        );
    }

    if (registerMutation.isSuccess) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <div className="max-w-md w-full p-8 space-y-4 text-center">
                    <CheckCircle className="h-16 w-16 mx-auto text-green-500" />
                    <h1 className="text-2xl font-bold text-foreground">
                        Presença Registrada!
                    </h1>
                    <p className="text-muted-foreground">
                        Sua presença foi registrada com sucesso no seminário:
                    </p>
                    <p className="font-semibold text-foreground">
                        {linkData.data.seminar.name}
                    </p>
                </div>
            </div>
        );
    }

    return null;
}
