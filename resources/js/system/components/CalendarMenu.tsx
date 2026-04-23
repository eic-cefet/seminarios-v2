import * as DropdownMenu from "@radix-ui/react-dropdown-menu";
import {
    CalendarPlus,
    ChevronDown,
    Download,
    ExternalLink,
} from "lucide-react";
import { createCalendarLinks, type CalendarEventDetails } from "@shared/lib/calendar";
import { cn } from "@shared/lib/utils";

interface CalendarMenuProps {
    event: CalendarEventDetails;
    className?: string;
    contentAlign?: "start" | "center" | "end";
}

export function CalendarMenu({
    event,
    className,
    contentAlign = "end",
}: CalendarMenuProps) {
    const links = createCalendarLinks(event);

    return (
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild>
                <button
                    type="button"
                    className={cn(
                        "inline-flex items-center justify-center gap-2 rounded-md px-4 py-3 text-sm font-medium transition-colors cursor-pointer",
                        className,
                    )}
                >
                    <CalendarPlus className="h-4 w-4" />
                    Adicionar ao calendário
                    <ChevronDown className="h-4 w-4" />
                </button>
            </DropdownMenu.Trigger>

            <DropdownMenu.Portal>
                <DropdownMenu.Content
                    sideOffset={8}
                    align={contentAlign}
                    className="min-w-[220px] rounded-md border border-gray-200 bg-white p-1 shadow-lg z-50"
                >
                    <DropdownMenu.Item asChild>
                        <a
                            href={links.google}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center justify-between rounded px-3 py-2 text-sm text-gray-700 outline-none hover:bg-gray-100"
                        >
                            <span>Google Calendar</span>
                            <ExternalLink className="h-4 w-4 text-gray-400" aria-hidden="true" />
                        </a>
                    </DropdownMenu.Item>
                    <DropdownMenu.Item asChild>
                        <a
                            href={links.outlook}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center justify-between rounded px-3 py-2 text-sm text-gray-700 outline-none hover:bg-gray-100"
                        >
                            <span>Outlook</span>
                            <ExternalLink className="h-4 w-4 text-gray-400" aria-hidden="true" />
                        </a>
                    </DropdownMenu.Item>
                    <DropdownMenu.Separator className="my-1 h-px bg-gray-100" />
                    <DropdownMenu.Item asChild>
                        <a
                            href={links.ics}
                            className="flex items-center justify-between rounded px-3 py-2 text-sm text-gray-700 outline-none hover:bg-gray-100"
                        >
                            <span>Baixar .ics</span>
                            <Download className="h-4 w-4 text-gray-400" aria-hidden="true" />
                        </a>
                    </DropdownMenu.Item>
                </DropdownMenu.Content>
            </DropdownMenu.Portal>
        </DropdownMenu.Root>
    );
}
