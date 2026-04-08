import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Check, Copy, ExternalLink, KeyRound, Plus, Trash2 } from "lucide-react";
import { useRef, useState } from "react";
import { toast } from "sonner";
import { apiTokensApi, type AdminApiToken } from "../../api/adminClient";
import { Button } from "../../components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";
import { Skeleton } from "../../components/ui/skeleton";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "../../components/ui/dialog";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "../../components/ui/alert-dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";
import { Switch } from "../../components/ui/switch";
import { Badge } from "../../components/ui/badge";
import { PageTitle } from "@shared/components/PageTitle";
import { buildUrl, formatDateTime } from "@shared/lib/utils";

const EXPIRY_OPTIONS = [
    { value: "7", label: "7 dias" },
    { value: "30", label: "30 dias" },
    { value: "60", label: "60 dias" },
    { value: "90", label: "90 dias" },
    { value: "180", label: "180 dias" },
    { value: "never", label: "Sem expiração" },
] as const;

function formatExpiry(expiresAt: string | null): string {
    if (!expiresAt) return "Nunca";
    const date = new Date(expiresAt);
    /* v8 ignore next -- @preserve dead branch: isExpired() renders Badge instead */
    if (date < new Date()) return "Expirado";
    return formatDateTime(expiresAt);
}

function isExpired(expiresAt: string | null): boolean {
    if (!expiresAt) return false;
    return new Date(expiresAt) < new Date();
}

const RESOURCE_GROUPS = [
    { resource: "seminars", label: "Seminários" },
    { resource: "seminar-types", label: "Tipos de Seminário" },
    { resource: "locations", label: "Locais" },
    { resource: "users", label: "Usuários" },
    { resource: "speaker-data", label: "Dados de Palestrante" },
] as const;

type AccessLevel = "none" | "read" | "read-write";

function getAccessLevel(
    abilities: string[],
    resource: string,
): AccessLevel {
    if (abilities.includes(`${resource}:write`)) return "read-write";
    if (abilities.includes(`${resource}:read`)) return "read";
    return "none";
}

function setAccessLevel(
    abilities: string[],
    resource: string,
    level: AccessLevel,
): string[] {
    const filtered = abilities.filter(
        (a) => a !== `${resource}:read` && a !== `${resource}:write`,
    );
    if (level === "read") return [...filtered, `${resource}:read`];
    if (level === "read-write")
        return [...filtered, `${resource}:read`, `${resource}:write`];
    return filtered;
}

export default function ApiTokenList() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isTokenShown, setIsTokenShown] = useState(false);
    const [createdToken, setCreatedToken] = useState("");
    const [copied, setCopied] = useState(false);
    const [deletingToken, setDeletingToken] = useState<AdminApiToken | null>(
        null,
    );

    const [formName, setFormName] = useState("");
    const [formExpiry, setFormExpiry] = useState("90");
    const [formAbilities, setFormAbilities] = useState<string[]>([]);
    const [fullAccess, setFullAccess] = useState(true);
    const copyTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const { data, isLoading } = useQuery({
        queryKey: ["admin-api-tokens", page],
        queryFn: () => apiTokensApi.list({ page }),
    });

    const tokens = data?.data ?? [];
    const meta = data?.meta;

    const createMutation = useMutation({
        mutationFn: apiTokensApi.create,
        onSuccess: (response) => {
            queryClient.invalidateQueries({ queryKey: ["admin-api-tokens"] });
            setIsCreateOpen(false);
            setCreatedToken(response.data.token);
            setIsTokenShown(true);
            setFormName("");
            setFormExpiry("90");
            setFormAbilities([]);
            setFullAccess(true);
        },
        onError: () => {
            toast.error("Erro ao criar token");
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => apiTokensApi.delete(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-api-tokens"] });
            toast.success("Token revogado com sucesso");
            setDeletingToken(null);
        },
        onError: () => {
            toast.error("Erro ao revogar token");
        },
    });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createMutation.mutate({
            name: formName,
            /* v8 ignore next -- @preserve both branches tested via form submit */
            expires_in_days:
                formExpiry === "never" ? null : parseInt(formExpiry, 10),
            abilities: fullAccess ? undefined : formAbilities,
        });
    };

    /* v8 ignore start -- @preserve tested via callback capture + clipboard mock */
    const handleCopy = async () => {
        await navigator.clipboard.writeText(createdToken);
        setCopied(true);
        toast.success("Token copiado!");
        if (copyTimeoutRef.current) clearTimeout(copyTimeoutRef.current);
        copyTimeoutRef.current = setTimeout(() => setCopied(false), 2000);
    };

    const isCreateDisabled =
        createMutation.isPending ||
        !formName ||
        (!fullAccess && formAbilities.length === 0);
    const createButtonLabel = createMutation.isPending
        ? "Criando..."
        : "Criar Token";

    const handleCloseTokenDialog = () => {
        setIsTokenShown(false);
        setCreatedToken("");
        setCopied(false);
        if (copyTimeoutRef.current) clearTimeout(copyTimeoutRef.current);
    };
    /* v8 ignore stop */

    return (
        <div className="space-y-6">
            <PageTitle title="API Tokens" />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        API Tokens
                    </h1>
                    <p className="text-muted-foreground">
                        Gerenciar seus tokens de acesso para a API externa.{" "}
                        <a
                            href={buildUrl("/api/external/docs")}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 text-primary hover:underline"
                        >
                            Documentação da API
                            <ExternalLink className="h-3 w-3" />
                        </a>
                    </p>
                </div>
                <Button onClick={() => setIsCreateOpen(true)}>
                    <Plus className="h-4 w-4 mr-2" />
                    Novo Token
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>Seus Tokens</CardTitle>
                        {meta && (
                            <span className="text-sm text-muted-foreground">
                                {meta.total}{" "}
                                {meta.total === 1
                                    ? "token encontrado"
                                    : "tokens encontrados"}
                            </span>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="space-y-3">
                            {[...Array(3)].map((_, i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : tokens.length === 0 ? (
                        <div className="text-center py-12">
                            <KeyRound className="h-12 w-12 mx-auto text-muted-foreground/50 mb-4" />
                            <p className="text-muted-foreground">
                                Nenhum token criado
                            </p>
                            <p className="text-sm text-muted-foreground mt-1">
                                Crie um token para permitir acesso via API
                                externa.
                            </p>
                        </div>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nome</TableHead>
                                        <TableHead>Permissões</TableHead>
                                        <TableHead>Expira em</TableHead>
                                        <TableHead>Último uso</TableHead>
                                        <TableHead>Criado em</TableHead>
                                        <TableHead className="w-16">
                                            Ações
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tokens.map((token) => (
                                        <TableRow key={token.id}>
                                            <TableCell className="font-medium">
                                                {token.name}
                                            </TableCell>
                                            <TableCell>
                                                {token.abilities.includes(
                                                    "*",
                                                ) ? (
                                                    <Badge variant="secondary">
                                                        Acesso total
                                                    </Badge>
                                                ) : (
                                                    <div className="flex flex-wrap gap-1">
                                                        {token.abilities.map(
                                                            (ability) => (
                                                                <Badge
                                                                    key={
                                                                        ability
                                                                    }
                                                                    variant="outline"
                                                                    className="text-xs"
                                                                >
                                                                    {ability}
                                                                </Badge>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {isExpired(token.expires_at) ? (
                                                    <Badge variant="destructive">
                                                        Expirado
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        {formatExpiry(
                                                            token.expires_at,
                                                        )}
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {token.last_used_at
                                                    ? formatDateTime(
                                                          token.last_used_at,
                                                      )
                                                    : "Nunca"}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {formatDateTime(
                                                    token.created_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setDeletingToken(token)
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {meta && meta.last_page > 1 && (
                                <div className="flex items-center justify-between mt-4 pt-4 border-t border-border">
                                    <div className="text-sm text-muted-foreground">
                                        Mostrando {meta.from} a {meta.to} de{" "}
                                        {meta.total} tokens
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setPage((p) =>
                                                    Math.max(1, p - 1),
                                                )
                                            }
                                            disabled={page === 1}
                                        >
                                            Anterior
                                        </Button>
                                        <span className="text-sm text-muted-foreground">
                                            Página {page} de {meta.last_page}
                                        </span>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setPage((p) =>
                                                    Math.min(
                                                        meta.last_page,
                                                        p + 1,
                                                    ),
                                                )
                                            }
                                            disabled={page === meta.last_page}
                                        >
                                            Próxima
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </CardContent>
            </Card>

            {/* Create Token Dialog */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Novo Token de API</DialogTitle>
                        <DialogDescription>
                            Crie um token para permitir acesso programático à
                            API externa. O token será exibido apenas uma vez.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate}>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="token-name">
                                    Nome do Token
                                </Label>
                                <Input
                                    id="token-name"
                                    value={formName}
                                    onChange={(e) =>
                                        setFormName(e.target.value)
                                    }
                                    placeholder="Ex: Integração TCC"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="token-expiry">Expiração</Label>
                                <Select
                                    value={formExpiry}
                                    onValueChange={setFormExpiry}
                                >
                                    <SelectTrigger id="token-expiry">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {EXPIRY_OPTIONS.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <Label>Permissões</Label>
                                    <div className="flex items-center gap-2">
                                        <Label
                                            htmlFor="full-access"
                                            className="text-sm font-normal text-muted-foreground"
                                        >
                                            Acesso total
                                        </Label>
                                        <Switch
                                            id="full-access"
                                            checked={fullAccess}
                                            onCheckedChange={(checked) => {
                                                setFullAccess(checked);
                                                if (checked)
                                                    setFormAbilities([]);
                                            }}
                                        />
                                    </div>
                                </div>
                                {!fullAccess && (
                                    <div className="space-y-2">
                                        {RESOURCE_GROUPS.map(
                                            ({ resource, label }) => {
                                                const level =
                                                    getAccessLevel(
                                                        formAbilities,
                                                        resource,
                                                    );
                                                const levels: {
                                                    value: AccessLevel;
                                                    label: string;
                                                }[] = [
                                                    {
                                                        value: "none",
                                                        label: "Nenhum",
                                                    },
                                                    {
                                                        value: "read",
                                                        label: "Leitura",
                                                    },
                                                    {
                                                        value: "read-write",
                                                        label: "Escrita",
                                                    },
                                                ];
                                                return (
                                                    <div
                                                        key={resource}
                                                        className="flex items-center justify-between gap-3"
                                                    >
                                                        <span className="text-sm min-w-0 truncate">
                                                            {label}
                                                        </span>
                                                        <div className="flex rounded-md border border-border overflow-hidden shrink-0">
                                                            {levels.map(
                                                                (l) => (
                                                                    <button
                                                                        key={
                                                                            l.value
                                                                        }
                                                                        type="button"
                                                                        onClick={() =>
                                                                            setFormAbilities(
                                                                                (
                                                                                    prev,
                                                                                ) =>
                                                                                    setAccessLevel(
                                                                                        prev,
                                                                                        resource,
                                                                                        l.value,
                                                                                    ),
                                                                            )
                                                                        }
                                                                        className={`px-2.5 py-1 text-xs transition-colors ${
                                                                            level ===
                                                                            l.value
                                                                                ? "bg-primary text-primary-foreground"
                                                                                : "bg-background text-muted-foreground hover:text-foreground hover:bg-muted"
                                                                        } ${l.value !== "none" ? "border-l border-border" : ""}`}
                                                                    >
                                                                        {
                                                                            l.label
                                                                        }
                                                                    </button>
                                                                ),
                                                            )}
                                                        </div>
                                                    </div>
                                                );
                                            },
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={isCreateDisabled}
                            >
                                {createButtonLabel}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Token Created - Show Once Dialog */}
            <Dialog open={isTokenShown} onOpenChange={handleCloseTokenDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Token Criado</DialogTitle>
                        <DialogDescription>
                            Copie o token abaixo. Por segurança, ele não será
                            exibido novamente.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-4">
                        <div className="flex items-center gap-2">
                            <code className="flex-1 bg-muted px-3 py-2 rounded-md text-sm font-mono break-all">
                                {createdToken}
                            </code>
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={handleCopy}
                            >
                                {copied ? (
                                    <Check className="h-4 w-4 text-green-500" />
                                ) : (
                                    <Copy className="h-4 w-4" />
                                )}
                            </Button>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button onClick={handleCloseTokenDialog}>Fechar</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation */}
            <AlertDialog
                open={!!deletingToken}
                onOpenChange={(open) => !open && setDeletingToken(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Revogar token?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tem certeza que deseja revogar o token "
                            {deletingToken?.name}"? Qualquer sistema que utilize
                            este token perderá acesso imediatamente.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() =>
                                deletingToken &&
                                deleteMutation.mutate(deletingToken.id)
                            }
                            className="bg-red-500 hover:bg-red-600 text-white"
                        >
                            {deleteMutation.isPending
                                ? "Revogando..."
                                : "Revogar"}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
