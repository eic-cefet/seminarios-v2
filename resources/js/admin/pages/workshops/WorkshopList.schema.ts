import { z } from "zod";

export const workshopFormSchema = z.object({
    name: z
        .string()
        .min(1, "Nome é obrigatório")
        .max(255, "Nome deve ter no máximo 255 caracteres"),
    description: z.string(),
    seminar_ids: z.array(z.number()),
});

export type WorkshopFormData = z.infer<typeof workshopFormSchema>;

export const workshopFormDefaults: WorkshopFormData = {
    name: "",
    description: "",
    seminar_ids: [],
};
