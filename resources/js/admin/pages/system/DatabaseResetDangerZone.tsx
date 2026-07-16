import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Trash2 } from "lucide-react";
import { toast } from "sonner";
import { buildUrl } from "@shared/lib/utils";
import { systemInfoApi } from "../../api/adminClient";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "../../components/ui/alert-dialog";
import { Button } from "../../components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";

const CONFIRMATION_PHRASE = "APAGAR BANCO";

export function DatabaseResetDangerZone() {
    const [open, setOpen] = useState(false);
    const [confirmation, setConfirmation] = useState("");

    const resetMutation = useMutation({
        mutationFn: () => systemInfoApi.resetDatabase(confirmation),
        onSuccess: ({ message }) => {
            toast.success(message);
            window.setTimeout(() => {
                window.location.href = buildUrl("/login");
            }, 1_000);
        },
        onError: (error) => {
            toast.error(
                error instanceof Error
                    ? error.message
                    : "Não foi possível recriar o banco de dados.",
            );
        },
    });

    const handleOpenChange = (nextOpen: boolean) => {
        if (resetMutation.isPending) {
            return;
        }

        setOpen(nextOpen);
        if (!nextOpen) {
            setConfirmation("");
        }
    };

    return (
        <Card className="border-destructive/50">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base text-destructive">
                    <Trash2 className="h-4 w-4" />
                    Zona de perigo
                </CardTitle>
                <CardDescription>
                    Recrie completamente o banco deste ambiente usando migrations e seeds.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AlertDialog open={open} onOpenChange={handleOpenChange}>
                    <AlertDialogTrigger asChild>
                        <Button
                            variant="destructive"
                            disabled={resetMutation.isPending}
                        >
                            Recriar banco de dados
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Recriar todo o banco de dados?
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                Todas as tabelas, usuários, sessões, auditorias e dados da
                                aplicação serão apagados e substituídos pelos dados dos seeds.
                                Uma falha durante a operação pode exigir recuperação via CLI.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="database-reset-confirmation">
                                Digite APAGAR BANCO para confirmar
                            </Label>
                            <Input
                                id="database-reset-confirmation"
                                value={confirmation}
                                onChange={(event) => setConfirmation(event.target.value)}
                                disabled={resetMutation.isPending}
                                autoComplete="off"
                            />
                        </div>

                        <AlertDialogFooter>
                            <AlertDialogCancel disabled={resetMutation.isPending}>
                                Cancelar
                            </AlertDialogCancel>
                            <AlertDialogAction asChild>
                                <Button
                                    variant="destructive"
                                    disabled={
                                        confirmation !== CONFIRMATION_PHRASE ||
                                        resetMutation.isPending
                                    }
                                    onClick={(event) => {
                                        event.preventDefault();
                                        resetMutation.mutate();
                                    }}
                                >
                                    {resetMutation.isPending
                                        ? "Recriando banco..."
                                        : "Recriar banco"}
                                </Button>
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </CardContent>
        </Card>
    );
}
