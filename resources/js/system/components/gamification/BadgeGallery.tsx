import { Button } from "@shared/components/Button";
import type { GamificationProfile } from "@shared/types";
import { useState } from "react";
import { BadgeCard } from "./BadgeCard";

interface BadgeGalleryProps {
    categories: GamificationProfile["categories"];
}

export function BadgeGallery({ categories }: BadgeGalleryProps) {
    const [selectedCategory, setSelectedCategory] = useState("all");
    const badges = selectedCategory === "all"
        ? categories.flatMap((category) => category.badges)
        : categories.find((category) => category.key === selectedCategory)?.badges ?? [];

    return (
        <section aria-labelledby="badge-gallery-heading">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="badge-gallery-heading" className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Galeria de conquistas
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Explore seus objetivos concluídos e os próximos desafios.
                    </p>
                </div>
                <div aria-label="Filtrar conquistas por categoria" className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant={selectedCategory === "all" ? "primary" : "outline"}
                        aria-pressed={selectedCategory === "all"}
                        onClick={() => setSelectedCategory("all")}
                    >
                        Todas
                    </Button>
                    {categories.map((category) => (
                        <Button
                            key={category.key}
                            type="button"
                            size="sm"
                            variant={selectedCategory === category.key ? "primary" : "outline"}
                            aria-pressed={selectedCategory === category.key}
                            onClick={() => setSelectedCategory(category.key)}
                        >
                            {category.label}
                        </Button>
                    ))}
                </div>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                {badges.map((badge) => (
                    <BadgeCard key={badge.key} badge={badge} />
                ))}
            </div>
        </section>
    );
}
