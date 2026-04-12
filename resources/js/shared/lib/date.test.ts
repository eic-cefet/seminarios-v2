import {
    formatDate,
    formatDateTime,
    formatDateTimeLong,
    formatDurationMinutes,
    isExpired,
    isToday,
    getYear,
    getSemester,
    toDatetimeLocal,
    compareDateDesc,
} from './date';

describe('formatDate', () => {
    it('formats UTC date string to dd/MM/yyyy in São Paulo timezone', () => {
        // 2026-03-15T10:00:00Z = 2026-03-15 07:00 BRT (UTC-3)
        expect(formatDate('2026-03-15T10:00:00Z')).toBe('15/03/2026');
    });

    it('handles date that crosses midnight in São Paulo timezone', () => {
        // 2026-03-16T02:00:00Z = 2026-03-15 23:00 BRT — still the 15th in SP
        expect(formatDate('2026-03-16T02:00:00Z')).toBe('15/03/2026');
    });

    it('accepts Date objects', () => {
        expect(formatDate(new Date('2026-12-25T12:00:00Z'))).toBe('25/12/2026');
    });
});

describe('formatDateTime', () => {
    it('formats date string with time in São Paulo timezone', () => {
        // 2026-03-15T17:30:00Z = 2026-03-15 14:30 BRT
        expect(formatDateTime('2026-03-15T17:30:00Z')).toBe('15/03/2026, 14:30');
    });

    it('handles timezone offset correctly', () => {
        // 2026-06-01T08:00:00Z = 2026-06-01 05:00 BRT
        expect(formatDateTime('2026-06-01T08:00:00Z')).toBe('01/06/2026, 05:00');
    });
});

describe('formatDateTimeLong', () => {
    it('formats with long Portuguese month name', () => {
        // 2026-03-15T17:30:00Z = 2026-03-15 14:30 BRT
        expect(formatDateTimeLong('2026-03-15T17:30:00Z')).toBe('15 de março de 2026, 14:30');
    });

    it('formats June correctly', () => {
        expect(formatDateTimeLong('2026-06-15T15:00:00Z')).toBe('15 de junho de 2026, 12:00');
    });
});

describe('formatDurationMinutes', () => {
    it('formats minutes-only durations', () => {
        expect(formatDurationMinutes(30)).toBe('30min');
    });

    it('formats whole-hour durations', () => {
        expect(formatDurationMinutes(120)).toBe('2h');
    });

    it('formats mixed hour and minute durations', () => {
        expect(formatDurationMinutes(90)).toBe('1h 30min');
    });
});

describe('isExpired', () => {
    it('returns true for past dates', () => {
        expect(isExpired('2020-01-01T00:00:00Z')).toBe(true);
    });

    it('returns false for future dates', () => {
        expect(isExpired('2099-12-31T23:59:59Z')).toBe(false);
    });

    it('accepts Date objects', () => {
        expect(isExpired(new Date('2020-01-01'))).toBe(true);
    });
});

describe('isToday', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        // Set to 2026-06-15 10:00 UTC = 2026-06-15 07:00 BRT
        vi.setSystemTime(new Date('2026-06-15T10:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('returns true for a date that is today in São Paulo timezone', () => {
        // 2026-06-15T18:00:00Z = 2026-06-15 15:00 BRT — same day
        expect(isToday('2026-06-15T18:00:00Z')).toBe(true);
    });

    it('returns false for yesterday', () => {
        expect(isToday('2026-06-14T12:00:00Z')).toBe(false);
    });

    it('returns false for tomorrow', () => {
        expect(isToday('2026-06-16T12:00:00Z')).toBe(false);
    });
});

describe('getYear', () => {
    it('returns current year', () => {
        expect(getYear()).toBeGreaterThanOrEqual(2026);
    });
});

describe('getSemester', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('returns semester 1 for January-June', () => {
        vi.setSystemTime(new Date('2026-03-15T12:00:00Z'));
        expect(getSemester()).toEqual({ year: 2026, semester: 1 });
    });

    it('returns semester 2 for July-December', () => {
        vi.setSystemTime(new Date('2026-09-15T12:00:00Z'));
        expect(getSemester()).toEqual({ year: 2026, semester: 2 });
    });
});

describe('toDatetimeLocal', () => {
    it('converts ISO string to datetime-local format in São Paulo timezone', () => {
        // 2026-03-15T17:30:00Z = 2026-03-15 14:30 BRT
        expect(toDatetimeLocal('2026-03-15T17:30:00Z')).toBe('2026-03-15T14:30');
    });
});

describe('compareDateDesc', () => {
    it('sorts dates in descending order (newest first)', () => {
        const dates = ['2026-01-01T00:00:00Z', '2026-06-01T00:00:00Z', '2026-03-01T00:00:00Z'];
        const sorted = [...dates].sort(compareDateDesc);
        expect(sorted).toEqual(['2026-06-01T00:00:00Z', '2026-03-01T00:00:00Z', '2026-01-01T00:00:00Z']);
    });
});
