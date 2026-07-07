import { useEffect, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Loader2, UserPlus } from "lucide-react";
import { toast } from "sonner";
import { analytics } from "@shared/lib/analytics";
import { registrationsApi } from "../api/adminClient";
import { Button } from "./ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "./ui/dialog";
import { Label } from "./ui/label";
import { SeminarCombobox, type SeminarOption } from "./SeminarCombobox";
import { UserMultiCombobox, type UserOption } from "./UserMultiCombobox";

interface AddRegistrationsModalProps {
    open: boolean;
    onClose: () => void;
    initialSeminar: SeminarOption | null;
}

const buildSuccessToast = (created: number, alreadyRegistered: number) => {
    const parts = [
        `${created} ${created === 1 ? "inscricao criada" : "inscricoes criadas"}`,
    ];
    if (alreadyRegistered > 0) {
        parts.push(
            `${alreadyRegistered} ja ${alreadyRegistered === 1 ? "inscrito" : "inscritos"}`,
        );
    }
    return parts.join(" · ");
};

export function AddRegistrationsModal({
    open,
    onClose,
    initialSeminar,
}: AddRegistrationsModalProps) {
    const queryClient = useQueryClient();
    const [seminar, setSeminar] = useState<SeminarOption | null>(
        initialSeminar,
    );
    const [users, setUsers] = useState<UserOption[]>([]);

    useEffect(() => {
        if (open) {
            setSeminar(initialSeminar);
            setUsers([]);
        }
    }, [open, initialSeminar]);

    const addMutation = useMutation({
        mutationFn: (payload: { seminar_id: number; user_ids: number[] }) =>
            registrationsApi.store(payload),
        onSuccess: (response, payload) => {
            toast.success(
                buildSuccessToast(
                    response.data.created,
                    response.data.already_registered,
                ),
            );
            analytics.event("admin_registrations_added", {
                seminar_id: payload.seminar_id,
                count: payload.user_ids.length,
            });
            queryClient.invalidateQueries({
                queryKey: ["admin-registrations"],
            });
            onClose();
        },
        onError: () => {
            toast.error("Erro ao adicionar inscricoes");
        },
    });

    const canSubmit =
        seminar !== null && users.length > 0 && !addMutation.isPending;

    const handleSubmit = () => {
        if (!seminar || users.length === 0) {
            return;
        }
        addMutation.mutate({
            seminar_id: seminar.id,
            user_ids: users.map((user) => user.id),
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen) {
                    onClose();
                }
            }}
        >
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Adicionar inscricoes</DialogTitle>
                    <DialogDescription>
                        Selecione o seminario e os usuarios. Os usuarios serao
                        marcados como presentes.
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="add-registrations-seminar">
                            Seminario
                        </Label>
                        <SeminarCombobox
                            id="add-registrations-seminar"
                            value={seminar}
                            onChange={setSeminar}
                            withAllOption={false}
                            modal
                            placeholder="Selecione o seminario"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="add-registrations-users">
                            Usuarios
                        </Label>
                        <UserMultiCombobox
                            id="add-registrations-users"
                            value={users}
                            onChange={setUsers}
                            modal
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={onClose}
                        disabled={addMutation.isPending}
                    >
                        Cancelar
                    </Button>
                    <Button onClick={handleSubmit} disabled={!canSubmit}>
                        {addMutation.isPending ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <UserPlus className="mr-2 h-4 w-4" />
                        )}
                        Adicionar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
