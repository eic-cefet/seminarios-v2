import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { debounce } from "lodash";
import { ArrowLeft, Plus, Search } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { toast } from "sonner";
import { usersApi, type AdminUser } from "../api/adminClient";
import { Button } from "./ui/button";
import { Checkbox } from "./ui/checkbox";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "./ui/dialog";
import { Input } from "./ui/input";
import { Label } from "./ui/label";
import { Separator } from "./ui/separator";

interface SpeakerSelectionModalProps {
    open: boolean;
    onClose: () => void;
    selectedIds: number[];
    onConfirm: (ids: number[], users: AdminUser[]) => void;
}

export function SpeakerSelectionModal({
    open,
    onClose,
    selectedIds,
    onConfirm,
}: SpeakerSelectionModalProps) {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [tempSelected, setTempSelected] = useState<number[]>(selectedIds);
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [createFormData, setCreateFormData] = useState({
        name: "",
        email: "",
        password: "",
        institution: "",
        description: "",
    });

    // Debounced search handler
    const debouncedSearch = useRef(
        debounce((value: string) => {
            setSearchTerm(value);
        }, 500),
    ).current;

    // Cleanup debounce on unmount
    useEffect(() => {
        return () => {
            debouncedSearch.cancel();
        };
    }, [debouncedSearch]);

    // Sync temp selected when modal opens or selectedIds change
    useEffect(() => {
        if (open) {
            setTempSelected(selectedIds);
        }
    }, [open, selectedIds]);

    const { data: usersData, isLoading } = useQuery({
        queryKey: ["admin-users", { search: searchTerm }],
        queryFn: () => usersApi.list({ search: searchTerm || undefined }),
        enabled: open && !showCreateForm,
    });

    const createUserMutation = useMutation({
        mutationFn: (data: typeof createFormData) =>
            usersApi.create({
                name: data.name,
                email: data.email,
                password: data.password,
                speaker_data: {
                    institution: data.institution,
                    description: data.description,
                },
            }),
        onSuccess: (response) => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Palestrante criado com sucesso");
            setTempSelected([...tempSelected, response.data.id]);
            setShowCreateForm(false);
            setCreateFormData({
                name: "",
                email: "",
                password: "",
                institution: "",
                description: "",
            });
        },
        onError: () => {
            toast.error("Erro ao criar palestrante");
        },
    });

    const toggleUser = (id: number) => {
        setTempSelected((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
        );
    };

    const handleSearchChange = (value: string) => {
        setSearch(value);
        debouncedSearch(value);
    };

    const handleConfirm = () => {
        // Get selected user objects
        const users = usersData?.data ?? [];
        const selectedUsers = users.filter((u) => tempSelected.includes(u.id));
        onConfirm(tempSelected, selectedUsers);
        onClose();
    };

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createUserMutation.mutate(createFormData);
    };

    const handleClose = () => {
        setSearch("");
        setSearchTerm("");
        setShowCreateForm(false);
        setCreateFormData({
            name: "",
            email: "",
            password: "",
            institution: "",
            description: "",
        });
        onClose();
    };

    const users = usersData?.data ?? [];

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {showCreateForm
                            ? "Criar Novo Palestrante"
                            : "Selecionar Palestrantes"}
                    </DialogTitle>
                </DialogHeader>

                {!showCreateForm ? (
                    <>
                        <div className="space-y-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Buscar usuários..."
                                    value={search}
                                    onChange={(e) =>
                                        handleSearchChange(e.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>

                            <div className="border rounded-md divide-y max-h-[300px] overflow-y-auto">
                                {isLoading ? (
                                    <div className="p-8 text-center text-muted-foreground">
                                        Carregando...
                                    </div>
                                ) : users.length === 0 ? (
                                    <div className="p-8 text-center text-muted-foreground">
                                        Nenhum usuário encontrado
                                    </div>
                                ) : (
                                    users.map((user) => (
                                        <div
                                            key={user.id}
                                            className="flex items-center gap-3 p-3 hover:bg-muted/50"
                                        >
                                            <Checkbox
                                                checked={tempSelected.includes(
                                                    user.id,
                                                )}
                                                onCheckedChange={() =>
                                                    toggleUser(user.id)
                                                }
                                            />
                                            <div className="flex-1">
                                                <p className="font-medium">
                                                    {user.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {user.email}
                                                </p>
                                                {user.speaker_data
                                                    ?.institution && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {
                                                            user.speaker_data
                                                                .institution
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowCreateForm(true)}
                                className="w-full"
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Criar Novo Palestrante
                            </Button>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleClose}
                            >
                                Cancelar
                            </Button>
                            <Button type="button" onClick={handleConfirm}>
                                Confirmar ({tempSelected.length})
                            </Button>
                        </DialogFooter>
                    </>
                ) : (
                    <form onSubmit={handleCreateSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="create-name">Nome *</Label>
                            <Input
                                id="create-name"
                                value={createFormData.name}
                                onChange={(e) =>
                                    setCreateFormData({
                                        ...createFormData,
                                        name: e.target.value,
                                    })
                                }
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="create-email">Email *</Label>
                            <Input
                                id="create-email"
                                type="email"
                                value={createFormData.email}
                                onChange={(e) =>
                                    setCreateFormData({
                                        ...createFormData,
                                        email: e.target.value,
                                    })
                                }
                                required
                            />
                        </div>

                        <Separator />

                        <div className="space-y-2">
                            <Label htmlFor="create-institution">
                                Instituição
                            </Label>
                            <Input
                                id="create-institution"
                                value={createFormData.institution}
                                onChange={(e) =>
                                    setCreateFormData({
                                        ...createFormData,
                                        institution: e.target.value,
                                    })
                                }
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="create-description">
                                Descrição
                            </Label>
                            <textarea
                                id="create-description"
                                value={createFormData.description}
                                onChange={(e) =>
                                    setCreateFormData({
                                        ...createFormData,
                                        description: e.target.value,
                                    })
                                }
                                className="w-full min-h-[100px] px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                placeholder="Breve descrição do palestrante..."
                            />
                        </div>

                        <Separator />

                        <div className="space-y-2">
                            <Label htmlFor="create-password">Senha</Label>
                            <Input
                                id="create-password"
                                type="password"
                                value={createFormData.password}
                                onChange={(e) =>
                                    setCreateFormData({
                                        ...createFormData,
                                        password: e.target.value,
                                    })
                                }
                                minLength={8}
                                placeholder="Deixe em branco para gerar automaticamente"
                            />
                            <p className="text-xs text-muted-foreground">
                                Se deixar em branco, uma senha aleatória será
                                gerada. O palestrante poderá usar "Esqueci minha
                                senha" para definir uma nova.
                            </p>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowCreateForm(false)}
                            >
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Voltar
                            </Button>
                            <Button
                                type="submit"
                                disabled={createUserMutation.isPending}
                            >
                                {createUserMutation.isPending
                                    ? "Criando..."
                                    : "Criar e Adicionar"}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
