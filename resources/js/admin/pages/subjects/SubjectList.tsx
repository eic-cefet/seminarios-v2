import { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Merge } from 'lucide-react';
import { toast } from 'sonner';
import { debounce } from 'lodash';
import { subjectsApi, type AdminSubject } from '../../api/adminClient';
import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../components/ui/table';
import { Skeleton } from '../../components/ui/skeleton';
import { Checkbox } from '../../components/ui/checkbox';
import { Badge } from '../../components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '../../components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '../../components/ui/alert-dialog';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { PageTitle } from '@shared/components/PageTitle';

export default function SubjectList() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [searchInput, setSearchInput] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isMergeDialogOpen, setIsMergeDialogOpen] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [editingSubject, setEditingSubject] = useState<AdminSubject | null>(null);
    const [deletingSubject, setDeletingSubject] = useState<AdminSubject | null>(null);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [targetId, setTargetId] = useState<string>('');
    const [newMergeName, setNewMergeName] = useState('');
    const [formData, setFormData] = useState({ name: '' });

    const { data, isLoading } = useQuery({
        queryKey: ['admin-subjects', { search: searchTerm, page }],
        queryFn: () => subjectsApi.list({ search: searchTerm || undefined, page }),
    });

    const createMutation = useMutation({
        mutationFn: (data: { name: string }) => subjectsApi.create(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-subjects'] });
            toast.success('Disciplina criada com sucesso');
            closeDialog();
        },
        onError: () => {
            toast.error('Erro ao criar disciplina');
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }: { id: number; data: { name: string } }) => subjectsApi.update(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-subjects'] });
            toast.success('Disciplina atualizada com sucesso');
            closeDialog();
        },
        onError: () => {
            toast.error('Erro ao atualizar disciplina');
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => subjectsApi.delete(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-subjects'] });
            toast.success('Disciplina excluida com sucesso');
            setIsDeleteDialogOpen(false);
            setDeletingSubject(null);
        },
        onError: (error: Error) => {
            if (error.message.includes('associado')) {
                toast.error('Esta disciplina possui seminarios associados');
            } else {
                toast.error('Erro ao excluir disciplina');
            }
        },
    });

    const mergeMutation = useMutation({
        mutationFn: (data: { target_id: number; source_ids: number[]; new_name?: string }) =>
            subjectsApi.merge(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-subjects'] });
            toast.success('Disciplinas mescladas com sucesso');
            closeMergeDialog();
        },
        onError: () => {
            toast.error('Erro ao mesclar disciplinas');
        },
    });

    const subjects = data?.data ?? [];
    const meta = data?.meta;

    const openCreateDialog = () => {
        setEditingSubject(null);
        setFormData({ name: '' });
        setIsDialogOpen(true);
    };

    const openEditDialog = (subject: AdminSubject) => {
        setEditingSubject(subject);
        setFormData({ name: subject.name });
        setIsDialogOpen(true);
    };

    const openDeleteDialog = (subject: AdminSubject) => {
        setDeletingSubject(subject);
        setIsDeleteDialogOpen(true);
    };

    const closeDialog = () => {
        setIsDialogOpen(false);
        setEditingSubject(null);
        setFormData({ name: '' });
    };

    const openMergeDialog = () => {
        const firstSelected = subjects.find((s) => selectedIds.includes(s.id));
        setTargetId(firstSelected?.id.toString() ?? '');
        setNewMergeName(firstSelected?.name ?? '');
        setIsMergeDialogOpen(true);
    };

    const closeMergeDialog = () => {
        setIsMergeDialogOpen(false);
        setSelectedIds([]);
        setTargetId('');
        setNewMergeName('');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingSubject) {
            updateMutation.mutate({ id: editingSubject.id, data: formData });
        } else {
            createMutation.mutate(formData);
        }
    };

    const handleMerge = () => {
        if (!targetId) return;
        const target = parseInt(targetId, 10);
        const sourceIds = selectedIds.filter((id) => id !== target);
        mergeMutation.mutate({
            target_id: target,
            source_ids: sourceIds,
            new_name: newMergeName || undefined,
        });
    };

    const toggleSelection = (id: number) => {
        setSelectedIds((prev) => (prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]));
    };

    const toggleAll = () => {
        if (selectedIds.length === subjects.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(subjects.map((s) => s.id));
        }
    };

    // Debounced search handler
    const debouncedSearch = useRef(
        debounce((value: string) => {
            setSearchTerm(value);
            setPage(1);
        }, 500)
    ).current;

    // Cleanup debounce on unmount
    useEffect(() => {
        return () => {
            debouncedSearch.cancel();
        };
    }, [debouncedSearch]);

    const handleSearch = (value: string) => {
        setSearchInput(value);
        debouncedSearch(value);
    };

    const handleClearFilters = () => {
        setSearchInput('');
        setSearchTerm('');
        setPage(1);
    };

    const selectedSubjects = subjects.filter((s) => selectedIds.includes(s.id));
    const totalSeminarsAffected = selectedSubjects.reduce((acc, s) => acc + (s.seminars_count ?? 0), 0);
    const hasFilters = searchTerm !== '';

    return (
        <div className="space-y-6">
            <PageTitle title="Tópicos" />
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Disciplinas</h1>
                    <p className="text-muted-foreground">Gerenciar disciplinas dos seminarios</p>
                </div>
                <div className="flex items-center gap-2">
                    {selectedIds.length >= 2 && (
                        <Button variant="outline" onClick={openMergeDialog}>
                            <Merge className="h-4 w-4 mr-2" />
                            Mesclar ({selectedIds.length})
                        </Button>
                    )}
                    <Button onClick={openCreateDialog}>
                        <Plus className="h-4 w-4 mr-2" />
                        Nova Disciplina
                    </Button>
                </div>
            </div>

            {/* Filters */}
            <Card>
                <CardContent className="pt-6">
                    <div className="flex items-center gap-4">
                        <div className="flex-1">
                            <Input
                                placeholder="Pesquisar por nome..."
                                value={searchInput}
                                onChange={(e) => handleSearch(e.target.value)}
                                className="max-w-sm"
                            />
                        </div>
                        {hasFilters && (
                            <Button variant="outline" onClick={handleClearFilters}>
                                Limpar filtros
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>Lista de Disciplinas</CardTitle>
                        {meta && (
                            <span className="text-sm text-muted-foreground">
                                {meta.total} disciplinas encontradas
                            </span>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="space-y-3">
                            {[...Array(5)].map((_, i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : subjects.length === 0 ? (
                        <p className="text-center text-muted-foreground py-8">Nenhuma disciplina cadastrada</p>
                    ) : (
                        <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">
                                        <Checkbox
                                            checked={selectedIds.length === subjects.length && subjects.length > 0}
                                            onCheckedChange={toggleAll}
                                        />
                                    </TableHead>
                                    <TableHead>Nome</TableHead>
                                    <TableHead>Seminarios</TableHead>
                                    <TableHead className="w-24">Acoes</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {subjects.map((subject) => (
                                    <TableRow key={subject.id}>
                                        <TableCell>
                                            <Checkbox
                                                checked={selectedIds.includes(subject.id)}
                                                onCheckedChange={() => toggleSelection(subject.id)}
                                            />
                                        </TableCell>
                                        <TableCell className="font-medium">{subject.name}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{subject.seminars_count ?? 0}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => openEditDialog(subject)}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => openDeleteDialog(subject)}
                                                    disabled={(subject.seminars_count ?? 0) > 0}
                                                >
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {meta && meta.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4 pt-4 border-t border-border">
                                <div className="text-sm text-muted-foreground">
                                    Mostrando {meta.from} a {meta.to} de {meta.total} disciplinas
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                                        disabled={page === 1}
                                    >
                                        Anterior
                                    </Button>
                                    <span className="text-sm text-muted-foreground">
                                        Pagina {page} de {meta.last_page}
                                    </span>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
                                        disabled={page === meta.last_page}
                                    >
                                        Proxima
                                    </Button>
                                </div>
                            </div>
                        )}
                        </>
                    )}
                </CardContent>
            </Card>

            {/* Create/Edit Dialog */}
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingSubject ? 'Editar Disciplina' : 'Nova Disciplina'}</DialogTitle>
                        <DialogDescription>
                            {editingSubject
                                ? 'Edite os dados da disciplina abaixo'
                                : 'Preencha os dados da nova disciplina'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nome</Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ name: e.target.value })}
                                    placeholder="Ex: Inteligencia Artificial"
                                    required
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeDialog}>
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={createMutation.isPending || updateMutation.isPending}
                            >
                                {createMutation.isPending || updateMutation.isPending
                                    ? 'Salvando...'
                                    : 'Salvar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Merge Dialog */}
            <Dialog open={isMergeDialogOpen} onOpenChange={setIsMergeDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Mesclar Disciplinas</DialogTitle>
                        <DialogDescription>
                            Selecione a disciplina destino e o nome final. As outras disciplinas serao removidas.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-4">
                            <p className="text-sm text-yellow-500">
                                <strong>{selectedIds.length}</strong> disciplinas serao mescladas, afetando{' '}
                                <strong>{totalSeminarsAffected}</strong> seminarios.
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label>Disciplinas selecionadas</Label>
                            <div className="flex flex-wrap gap-2">
                                {selectedSubjects.map((s) => (
                                    <Badge key={s.id} variant="secondary">
                                        {s.name} ({s.seminars_count ?? 0})
                                    </Badge>
                                ))}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="target">Disciplina destino (sera mantida)</Label>
                            <Select value={targetId} onValueChange={setTargetId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecione a disciplina destino" />
                                </SelectTrigger>
                                <SelectContent>
                                    {selectedSubjects.map((s) => (
                                        <SelectItem key={s.id} value={s.id.toString()}>
                                            {s.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="newName">Nome final (opcional)</Label>
                            <Input
                                id="newName"
                                value={newMergeName}
                                onChange={(e) => setNewMergeName(e.target.value)}
                                placeholder="Deixe vazio para manter o nome atual"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={closeMergeDialog}>
                            Cancelar
                        </Button>
                        <Button onClick={handleMerge} disabled={!targetId || mergeMutation.isPending}>
                            {mergeMutation.isPending ? 'Mesclando...' : 'Mesclar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={isDeleteDialogOpen} onOpenChange={setIsDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Excluir disciplina?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tem certeza que deseja excluir a disciplina "{deletingSubject?.name}"? Esta acao nao
                            pode ser desfeita.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => deletingSubject && deleteMutation.mutate(deletingSubject.id)}
                            className="bg-red-500 hover:bg-red-600"
                        >
                            {deleteMutation.isPending ? 'Excluindo...' : 'Excluir'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
