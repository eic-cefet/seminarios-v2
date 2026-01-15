import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { useNavigate, useParams } from "react-router-dom";
import { toast } from "sonner";
import { PageTitle } from "@shared/components/PageTitle";
import type { AdminUser } from "../../api/adminClient";
import { seminarsApi } from "../../api/adminClient";
import { MarkdownEditor } from "../../components/MarkdownEditor";
import { SpeakerSelectionModal } from "../../components/SpeakerSelectionModal";
import { SubjectMultiSelect } from "../../components/SubjectMultiSelect";
import { Badge } from "../../components/ui/badge";
import { Button } from "../../components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";
import { Switch } from "../../components/ui/switch";
import { seminarSchema, type SeminarFormData } from "./seminarSchema";

function generateSlug(name: string): string {
    return name
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "");
}

// Helper API functions for reference data
const API_BASE = app.API_URL + "/admin";

const listTypes = async () => {
    const response = await fetch(`${API_BASE}/seminar-types`, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    });
    return response.json();
};

const listWorkshops = async () => {
    const response = await fetch(`${API_BASE}/workshops`, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    });
    return response.json();
};

const listLocations = async () => {
    const response = await fetch(`${API_BASE}/locations`, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    });
    return response.json();
};

export default function SeminarForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const isEditMode = !!id;

    const [isSpeakerModalOpen, setIsSpeakerModalOpen] = useState(false);
    const [selectedSpeakers, setSelectedSpeakers] = useState<AdminUser[]>([]);

    const { data: seminarData } = useQuery({
        queryKey: ["admin-seminar", id],
        queryFn: () => seminarsApi.get(Number(id)),
        enabled: isEditMode,
    });

    const { data: locationsData } = useQuery({
        queryKey: ["admin-locations-all"],
        queryFn: listLocations,
    });

    const { data: typesData } = useQuery({
        queryKey: ["admin-seminar-types"],
        queryFn: listTypes,
    });

    const { data: workshopsData } = useQuery({
        queryKey: ["admin-workshops"],
        queryFn: listWorkshops,
    });

    const {
        register,
        handleSubmit,
        watch,
        setValue,
        reset,
        formState: { errors },
    } = useForm<SeminarFormData>({
        resolver: zodResolver(seminarSchema),
        defaultValues: {
            name: "",
            description: "",
            scheduled_at: "",
            link: "",
            active: true,
            seminar_location_id: undefined,
            seminar_type_id: undefined,
            workshop_id: undefined,
            subject_names: [],
            speaker_ids: [],
        },
    });

    // Auto-generate slug from name
    const name = watch("name");
    const generatedSlug = name ? generateSlug(name) : "";

    // Load seminar data in edit mode - wait for all reference data to be loaded first
    useEffect(() => {
        if (
            seminarData?.data &&
            locationsData?.data &&
            typesData?.data &&
            workshopsData?.data
        ) {
            const seminar = seminarData.data;

            // Convert scheduled_at from ISO to datetime-local format
            let scheduledAt = "";
            if (seminar.scheduled_at) {
                const date = new Date(seminar.scheduled_at);
                scheduledAt = new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                )
                    .toISOString()
                    .slice(0, 16);
            }

            // Handle IDs - try both flat and nested structures
            const locationId = seminar.seminar_location_id ?? seminar.location?.id;
            const typeId = seminar.seminar_type_id ?? seminar.seminar_type?.id;
            const workshopId = seminar.workshop_id ?? seminar.workshop?.id;

            // Use reset to load all data at once
            reset({
                name: seminar.name || "",
                description: seminar.description || "",
                scheduled_at: scheduledAt,
                link: seminar.link || "",
                active: seminar.active ?? true,
                seminar_location_id: locationId,
                seminar_type_id: typeId,
                workshop_id: workshopId,
                subject_names: seminar.subjects?.map((s: any) => s.name) || [],
                speaker_ids: seminar.speakers?.map((s: any) => s.id) || [],
            });

            setSelectedSpeakers(seminar.speakers || []);
        }
    }, [seminarData, locationsData, typesData, workshopsData, reset]);

    const createMutation = useMutation({
        mutationFn: (data: SeminarFormData) => seminarsApi.create(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-seminars"] });
            toast.success("Seminário criado com sucesso");
            navigate("/seminars");
        },
        onError: () => {
            toast.error("Erro ao criar seminário");
        },
    });

    const updateMutation = useMutation({
        mutationFn: (data: SeminarFormData) =>
            seminarsApi.update(Number(id), data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-seminars"] });
            queryClient.invalidateQueries({ queryKey: ["admin-seminar", id] });
            toast.success("Seminário atualizado com sucesso");
            navigate("/seminars");
        },
        onError: () => {
            toast.error("Erro ao atualizar seminário");
        },
    });

    const onSubmit = (data: SeminarFormData) => {
        if (isEditMode) {
            updateMutation.mutate(data);
        } else {
            createMutation.mutate(data);
        }
    };

    const handleSpeakerConfirm = (ids: number[], users: AdminUser[]) => {
        setValue("speaker_ids", ids);
        setSelectedSpeakers(users);
    };

    const locations = locationsData?.data ?? [];
    const types = typesData?.data ?? [];
    const workshops = workshopsData?.data ?? [];

    return (
        <>
            <PageTitle title={isEditMode ? "Editar Seminário" : "Novo Seminário"} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">
                            {isEditMode ? "Editar Seminário" : "Novo Seminário"}
                        </h1>
                        <p className="text-muted-foreground">
                            {isEditMode
                                ? "Atualize os dados do seminário"
                                : "Preencha os dados do novo seminário"}
                        </p>
                    </div>
                </div>

            <form onSubmit={handleSubmit(onSubmit)}>
                {/* Grid Layout for Desktop */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* Basic Information - 8 cols on desktop */}
                    <Card className="lg:col-span-8">
                        <CardHeader>
                            <CardTitle>Informações Básicas</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nome *</Label>
                                <Input
                                    id="name"
                                    {...register("name")}
                                    placeholder="Ex: Inteligência Artificial na Prática"
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-500">
                                        {errors.name.message}
                                    </p>
                                )}
                            </div>

                            {/* Slug display (read-only) */}
                            {name && (
                                <div className="space-y-2">
                                    <Label>Slug (gerado automaticamente)</Label>
                                    <div className="px-3 py-2 bg-muted rounded-md text-sm text-muted-foreground">
                                        {generatedSlug}
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <MarkdownEditor
                                    label="Descrição"
                                    value={watch("description") || ""}
                                    onChange={(value) =>
                                        setValue("description", value)
                                    }
                                    error={errors.description?.message}
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <Switch
                                    checked={watch("active")}
                                    onCheckedChange={(checked) =>
                                        setValue("active", checked)
                                    }
                                />
                                <Label>Seminário ativo</Label>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Schedule & Links - 4 cols on desktop */}
                    <Card className="lg:col-span-4">
                        <CardHeader>
                            <CardTitle>Agendamento</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="scheduled_at">
                                    Data e Hora *
                                </Label>
                                <Input
                                    id="scheduled_at"
                                    type="datetime-local"
                                    {...register("scheduled_at")}
                                />
                                {errors.scheduled_at && (
                                    <p className="text-sm text-red-500">
                                        {errors.scheduled_at.message}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="link">Link (opcional)</Label>
                                <Input
                                    id="link"
                                    type="url"
                                    {...register("link")}
                                    placeholder="https://exemplo.com/seminario"
                                />
                                {errors.link && (
                                    <p className="text-sm text-red-500">
                                        {errors.link.message}
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Location & Category - 4 cols on desktop */}
                    <Card className="lg:col-span-4">
                        <CardHeader>
                            <CardTitle>Localização e Categoria</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="location">Local *</Label>
                                <Select
                                    value={
                                        watch(
                                            "seminar_location_id"
                                        )?.toString() || ""
                                    }
                                    onValueChange={(value) =>
                                        setValue(
                                            "seminar_location_id",
                                            Number(value)
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecione um local" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {locations.map((loc: any) => (
                                            <SelectItem
                                                key={loc.id}
                                                value={loc.id.toString()}
                                            >
                                                {loc.name} (Cap:{" "}
                                                {loc.max_vacancies})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.seminar_location_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.seminar_location_id.message}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="type">Tipo</Label>
                                <Select
                                    value={
                                        watch("seminar_type_id")?.toString() ||
                                        "none"
                                    }
                                    onValueChange={(value) =>
                                        setValue(
                                            "seminar_type_id",
                                            value === "none"
                                                ? undefined
                                                : Number(value)
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Nenhum tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Nenhum tipo
                                        </SelectItem>
                                        {types.map((type: any) => (
                                            <SelectItem
                                                key={type.id}
                                                value={type.id.toString()}
                                            >
                                                {type.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="workshop">Workshop</Label>
                                <Select
                                    value={
                                        watch("workshop_id")?.toString() ||
                                        "none"
                                    }
                                    onValueChange={(value) =>
                                        setValue(
                                            "workshop_id",
                                            value === "none"
                                                ? undefined
                                                : Number(value)
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Nenhum workshop" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Nenhum workshop
                                        </SelectItem>
                                        {workshops.map((workshop: any) => (
                                            <SelectItem
                                                key={workshop.id}
                                                value={workshop.id.toString()}
                                            >
                                                {workshop.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Subjects - 4 cols on desktop */}
                    <Card className="lg:col-span-4">
                        <CardHeader>
                            <CardTitle>Disciplinas *</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <SubjectMultiSelect
                                value={watch("subject_names")}
                                onChange={(values) =>
                                    setValue("subject_names", values)
                                }
                                error={errors.subject_names?.message}
                            />
                        </CardContent>
                    </Card>

                    {/* Speakers - 4 cols on desktop */}
                    <Card className="lg:col-span-4">
                        <CardHeader>
                            <CardTitle>Palestrantes *</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap gap-2">
                                {selectedSpeakers.length > 0 ? (
                                    selectedSpeakers.map((speaker) => (
                                        <Badge
                                            key={speaker.id}
                                            variant="secondary"
                                        >
                                            {speaker.name}
                                        </Badge>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Nenhum palestrante selecionado
                                    </p>
                                )}
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsSpeakerModalOpen(true)}
                            >
                                Selecionar Palestrantes
                            </Button>

                            {errors.speaker_ids && (
                                <p className="text-sm text-red-500">
                                    {errors.speaker_ids.message}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Form Actions */}
                <div className="flex items-center gap-4 mt-6">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => navigate("/admin/seminars")}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        disabled={
                            createMutation.isPending || updateMutation.isPending
                        }
                    >
                        {createMutation.isPending || updateMutation.isPending
                            ? "Salvando..."
                            : isEditMode
                            ? "Atualizar Seminário"
                            : "Criar Seminário"}
                    </Button>
                </div>
            </form>

            <SpeakerSelectionModal
                open={isSpeakerModalOpen}
                onClose={() => setIsSpeakerModalOpen(false)}
                selectedIds={watch("speaker_ids")}
                onConfirm={handleSpeakerConfirm}
            />
            </div>
        </>
    );
}
