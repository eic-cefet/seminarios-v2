import { seminarSchema } from './seminarSchema';

describe('seminarSchema', () => {
    const validData = {
        name: 'Test Seminar',
        scheduled_at: '2026-06-15T14:00',
        active: true,
        seminar_location_id: 1,
        subject_names: ['Topic 1'],
        speaker_ids: [1],
    };

    it('validates correct data', () => {
        const result = seminarSchema.safeParse(validData);
        expect(result.success).toBe(true);
    });

    it('requires name', () => {
        const result = seminarSchema.safeParse({ ...validData, name: '' });
        expect(result.success).toBe(false);
        if (!result.success) {
            expect(result.error.issues[0].path).toContain('name');
        }
    });

    it('requires scheduled_at', () => {
        const result = seminarSchema.safeParse({ ...validData, scheduled_at: '' });
        expect(result.success).toBe(false);
    });

    it('requires at least one subject', () => {
        const result = seminarSchema.safeParse({ ...validData, subject_names: [] });
        expect(result.success).toBe(false);
        if (!result.success) {
            const subjectError = result.error.issues.find((i) => i.path.includes('subject_names'));
            expect(subjectError).toBeDefined();
        }
    });

    it('requires at least one speaker', () => {
        const result = seminarSchema.safeParse({ ...validData, speaker_ids: [] });
        expect(result.success).toBe(false);
        if (!result.success) {
            const speakerError = result.error.issues.find((i) => i.path.includes('speaker_ids'));
            expect(speakerError).toBeDefined();
        }
    });

    it('validates room_link as URL when provided', () => {
        const result = seminarSchema.safeParse({ ...validData, room_link: 'not-a-url' });
        expect(result.success).toBe(false);
    });

    it('allows empty room_link', () => {
        const result = seminarSchema.safeParse({ ...validData, room_link: '' });
        expect(result.success).toBe(true);
    });

    it('allows valid URL for room_link', () => {
        const result = seminarSchema.safeParse({ ...validData, room_link: 'https://meet.google.com/abc' });
        expect(result.success).toBe(true);
    });

    it('allows optional fields to be omitted', () => {
        const result = seminarSchema.safeParse(validData);
        expect(result.success).toBe(true);
    });

    it('requires seminar_location_id', () => {
        const { seminar_location_id, ...withoutLocation } = validData;
        const result = seminarSchema.safeParse(withoutLocation);
        expect(result.success).toBe(false);
    });
});
