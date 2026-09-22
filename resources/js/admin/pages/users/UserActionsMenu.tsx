import { useState } from "react";
import * as DropdownMenu from "@radix-ui/react-dropdown-menu";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Ellipsis, LogIn, Pencil, RotateCcw, ShieldCheck, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { useAuth } from "@shared/contexts/AuthContext";
import { buildUrl } from "@shared/lib/utils";
import { usersApi, type AdminUser } from "../../api/adminClient";
import { Button } from "../../components/ui/button";
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from "../../components/ui/alert-dialog";

interface UserActionsMenuProps {
    user: AdminUser;
    archived?: boolean;
    restoring?: boolean;
    onEdit: (user: AdminUser) => void;
    onLgpd: (user: AdminUser) => void;
    onDelete: (user: AdminUser) => void;
    onRestore: (user: AdminUser) => void;
}

const itemClassName = "flex cursor-pointer items-center gap-3 rounded-sm px-3 py-2 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0";

export function UserActionsMenu({ user, archived, restoring, onEdit, onLgpd, onDelete, onRestore }: UserActionsMenuProps) {
    const { user: actor } = useAuth();
    const queryClient = useQueryClient();
    const [confirming, setConfirming] = useState(false);
    const impersonation = useMutation({
        mutationKey: ["impersonate-user"],
        mutationFn: () => usersApi.impersonate(user.id),
        onSuccess: () => {
            queryClient.clear();
            window.location.href = buildUrl("/");
        },
        onError: () => toast.error("Não foi possível acessar como usuário. Tente novamente."),
    });
    const canImpersonate = actor?.roles?.includes("admin")
        && !actor.is_impersonating && actor.id !== user.id
        && !user.roles.includes("admin") && !user.deleted_at;

    return (
        <>
            <DropdownMenu.Root>
                <DropdownMenu.Trigger asChild>
                    <Button variant="ghost" size="icon" aria-label={`Ações de ${user.name}`} disabled={impersonation.isPending}>
                        <Ellipsis aria-hidden="true" />
                    </Button>
                </DropdownMenu.Trigger>
                <DropdownMenu.Portal>
                    <DropdownMenu.Content align="end" sideOffset={4} collisionPadding={12}
                        className="z-50 min-w-56 rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md">
                        {archived ? (
                            <DropdownMenu.Item className={itemClassName} disabled={restoring} onSelect={() => onRestore(user)}>
                                <RotateCcw aria-hidden="true" /> Restaurar
                            </DropdownMenu.Item>
                        ) : (
                            <>
                                <DropdownMenu.Item className={itemClassName} onSelect={() => onEdit(user)}>
                                    <Pencil aria-hidden="true" /> Editar
                                </DropdownMenu.Item>
                                <DropdownMenu.Item className={itemClassName} onSelect={() => onLgpd(user)}>
                                    <ShieldCheck aria-hidden="true" /> Dados LGPD
                                </DropdownMenu.Item>
                                {canImpersonate && (
                                    <DropdownMenu.Item className={itemClassName} onSelect={() => setConfirming(true)}>
                                        <LogIn aria-hidden="true" /> Acessar como usuário
                                    </DropdownMenu.Item>
                                )}
                                <DropdownMenu.Separator className="my-1 h-px bg-border" />
                                <DropdownMenu.Item
                                    className="flex cursor-pointer items-center gap-3 rounded-sm px-3 py-2 text-sm text-red-600 outline-none data-[highlighted]:bg-red-50 data-[highlighted]:text-red-700 dark:text-red-400 dark:data-[highlighted]:bg-red-950 dark:data-[highlighted]:text-red-300"
                                    onSelect={() => onDelete(user)}>
                                    <Trash2 className="size-4" aria-hidden="true" /> Excluir
                                </DropdownMenu.Item>
                            </>
                        )}
                    </DropdownMenu.Content>
                </DropdownMenu.Portal>
            </DropdownMenu.Root>
            <AlertDialog open={confirming} onOpenChange={(open) => { if (!impersonation.isPending) setConfirming(open); }}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Acessar como {user.name}?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Você acessará a conta {user.email} com as permissões deste usuário.
                            As ações serão registradas em auditoria. Use “Voltar ao administrador” para encerrar o acesso.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={impersonation.isPending}>Cancelar</AlertDialogCancel>
                        <AlertDialogAction disabled={impersonation.isPending} onClick={(event) => {
                            event.preventDefault();
                            impersonation.mutate();
                        }}>
                            {impersonation.isPending ? "Acessando..." : "Acessar como usuário"}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
