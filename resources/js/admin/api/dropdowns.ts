import { fetchAdminApi } from "./_base";

// Simplified types for dropdown lists
export interface LocationDropdownItem {
    id: number;
    name: string;
    max_vacancies: number;
}

export interface SeminarTypeDropdownItem {
    id: number;
    name: string;
}

export interface WorkshopDropdownItem {
    id: number;
    name: string;
}

// Dropdown helpers (used by SeminarForm, SemestralReport, etc.)
export const dropdownApi = {
    seminarTypes: () =>
        fetchAdminApi<{ data: SeminarTypeDropdownItem[] }>("/seminar-types"),
    workshops: () =>
        fetchAdminApi<{ data: WorkshopDropdownItem[] }>("/workshops-dropdown"),
    locations: () =>
        fetchAdminApi<{ data: LocationDropdownItem[] }>("/locations-dropdown"),
    courses: () =>
        fetchAdminApi<{ data: { value: string | number; label: string }[] }>("/reports/courses"),
};
