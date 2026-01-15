import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Copy, QrCode } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { presenceLinkApi } from "../api/adminClient";
import { Button } from "./ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "./ui/dialog";
import { Label } from "./ui/label";
import { Separator } from "./ui/separator";
import { Switch } from "./ui/switch";

interface PresenceLinkModalProps {
    open: boolean;
    onClose: () => void;
    seminarId: number;
    seminarName: string;
}

export function PresenceLinkModal({
    open,
    onClose,
    seminarId,
    seminarName,
}: PresenceLinkModalProps) {
    const queryClient = useQueryClient();
    const [hasLink, setHasLink] = useState(false);

    const { data, isLoading, refetch } = useQuery({
        queryKey: ["presence-link", seminarId],
        queryFn: () => presenceLinkApi.get(seminarId),
        enabled: open,
    });

    const presenceLink = data?.data;

    useEffect(() => {
        if (presenceLink) {
            setHasLink(true);
        } else {
            setHasLink(false);
        }
    }, [presenceLink]);

    const createMutation = useMutation({
        mutationFn: () => presenceLinkApi.create(seminarId),
        onSuccess: () => {
            refetch();
            toast.success("Link de presença criado com sucesso");
        },
        onError: () => {
            toast.error("Erro ao criar link de presença");
        },
    });

    const toggleMutation = useMutation({
        mutationFn: () => presenceLinkApi.toggle(seminarId),
        onSuccess: () => {
            refetch();
            queryClient.invalidateQueries({
                queryKey: ["presence-link", seminarId],
            });
            toast.success("Status do link atualizado");
        },
        onError: () => {
            toast.error("Erro ao atualizar status do link");
        },
    });

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        toast.success("Link copiado para a área de transferência");
    };

    const formatExpiresAt = (expiresAt: string | undefined) => {
        if (!expiresAt) return "N/A";
        const date = new Date(expiresAt);
        return date.toLocaleString("pt-BR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <QrCode className="h-5 w-5" />
                        Link de Presença
                    </DialogTitle>
                    <DialogDescription>
                        Gerenciar link de presença para:{" "}
                        <strong>{seminarName}</strong>
                    </DialogDescription>
                </DialogHeader>

                {isLoading ? (
                    <div className="py-8 text-center text-muted-foreground">
                        Carregando...
                    </div>
                ) : !hasLink || !presenceLink ? (
                    <div className="space-y-4 py-4">
                        <p className="text-sm text-muted-foreground">
                            Nenhum link de presença foi criado para este
                            seminário ainda.
                        </p>
                        <Button
                            onClick={() => createMutation.mutate()}
                            disabled={createMutation.isPending}
                        >
                            {createMutation.isPending
                                ? "Criando..."
                                : "Criar Link de Presença"}
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {/* Status and Toggle */}
                        <div className="flex items-center justify-between p-4 border rounded-md">
                            <div className="space-y-1">
                                <Label htmlFor="link-active">
                                    Status do Link
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    {presenceLink.active
                                        ? "Link ativo - usuários podem registrar presença"
                                        : "Link inativo - presença não pode ser registrada"}
                                </p>
                            </div>
                            <Switch
                                id="link-active"
                                checked={presenceLink.active}
                                onCheckedChange={() => toggleMutation.mutate()}
                                disabled={toggleMutation.isPending}
                            />
                        </div>

                        {/* Expiration Info */}
                        <div className="space-y-2">
                            <Label>Informações</Label>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="text-muted-foreground">
                                        Expira em:
                                    </span>
                                    <p className="font-medium">
                                        {formatExpiresAt(
                                            presenceLink.expires_at
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">
                                        Status:
                                    </span>
                                    <p
                                        className={`font-medium ${
                                            presenceLink.is_valid
                                                ? "text-green-600"
                                                : "text-red-600"
                                        }`}
                                    >
                                        {presenceLink.is_valid
                                            ? "Válido"
                                            : presenceLink.is_expired
                                            ? "Expirado"
                                            : "Inativo"}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Separator />

                        {/* QR Code */}
                        {presenceLink.active && (
                            <div className="space-y-4">
                                <Label>QR Code</Label>
                                <div className="flex justify-center p-4 bg-white rounded-md border">
                                    <img
                                        src={presenceLink.qr_code}
                                        alt="QR Code de Presença"
                                        className="w-64 h-64"
                                    />
                                </div>
                                <p className="text-xs text-center text-muted-foreground">
                                    Usuários podem escanear este QR code para
                                    registrar presença
                                </p>
                            </div>
                        )}

                        <Separator />

                        {/* Raw Link */}
                        <div className="space-y-2">
                            <Label>Link Direto</Label>
                            <div className="flex items-center gap-2">
                                <div className="flex-1 px-3 py-2 bg-muted rounded-md text-sm font-mono overflow-x-auto">
                                    {presenceLink.url}
                                </div>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() =>
                                        copyToClipboard(presenceLink.url)
                                    }
                                >
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Compartilhe este link diretamente se preferir
                            </p>
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
