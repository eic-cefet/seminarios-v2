import { Button } from "@shared/components/Button";
import { ROUTES } from "@shared/config/routes";
import type { GamificationSyncDelta } from "@shared/types";
import * as Dialog from "@radix-ui/react-dialog";
import { Sparkles, X } from "lucide-react";
import type { RefObject } from "react";
import { useRef } from "react";
import { Link } from "react-router-dom";
import { BadgeCard } from "./BadgeCard";

interface AchievementUnlockDialogProps {
    delta: GamificationSyncDelta | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    returnFocusRef?: RefObject<HTMLElement | null>;
}

export function AchievementUnlockDialog({
    delta,
    open,
    onOpenChange,
    returnFocusRef,
}: AchievementUnlockDialogProps) {
    const previousFocus = useRef<HTMLElement | null>(null);
    const shouldCelebrate = Boolean(
        delta && (delta.xp_earned > 0 || delta.new_badges.length > 0),
    );

    if (!delta || !shouldCelebrate) {
        return null;
    }

    const title =
        delta.new_badges.length === 0
            ? "Você ganhou XP!"
            : delta.new_badges.length === 1
              ? "Conquista desbloqueada!"
              : "Novas conquistas!";

    return (
        <Dialog.Root open={open} onOpenChange={onOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-50 bg-gray-950/70 transition-opacity duration-200 data-[state=closed]:opacity-0 motion-reduce:transition-none" />
                <Dialog.Content
                    className="fixed left-1/2 top-1/2 z-50 flex max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl -translate-x-1/2 -translate-y-1/2 flex-col gap-5 overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl transition duration-200 data-[state=closed]:scale-95 data-[state=closed]:opacity-0 dark:border-gray-700 dark:bg-gray-900 motion-reduce:transition-none sm:p-8"
                    onOpenAutoFocus={() => {
                        previousFocus.current =
                            document.activeElement instanceof HTMLElement
                                ? document.activeElement
                                : null;
                    }}
                    onCloseAutoFocus={(event) => {
                        event.preventDefault();
                        if (returnFocusRef?.current?.isConnected) {
                            returnFocusRef.current.focus();
                        } else if (previousFocus.current?.isConnected) {
                            previousFocus.current.focus();
                        }
                    }}
                >
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-200">
                                <Sparkles aria-hidden="true" className="h-6 w-6" />
                            </span>
                            <div>
                                <Dialog.Title className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {title}
                                </Dialog.Title>
                                <Dialog.Description className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Seu progresso foi atualizado.
                                </Dialog.Description>
                            </div>
                        </div>
                        <Dialog.Close asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Fechar"
                                className="shrink-0"
                            >
                                <X aria-hidden="true" />
                            </Button>
                        </Dialog.Close>
                    </div>

                    <div className="rounded-xl bg-primary-50 px-5 py-4 text-center dark:bg-primary-950">
                        <p className="text-3xl font-black text-primary-700 dark:text-primary-200">
                            +{delta.xp_earned} XP
                        </p>
                        <p className="mt-1 font-semibold text-gray-800 dark:text-gray-100">
                            Nível {delta.level.level} · {delta.level.rank}
                        </p>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Total: {delta.total_xp} XP
                        </p>
                    </div>

                    {delta.new_badges.length > 0 && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {delta.new_badges.map((badge) => (
                                <BadgeCard key={badge.key} badge={badge} />
                            ))}
                        </div>
                    )}

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button asChild variant="outline">
                            <Link to={ROUTES.SYSTEM.ACHIEVEMENTS}>
                                Ver todas as conquistas
                            </Link>
                        </Button>
                        <Dialog.Close asChild>
                            <Button type="button" variant="primary">
                                Continuar
                            </Button>
                        </Dialog.Close>
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
