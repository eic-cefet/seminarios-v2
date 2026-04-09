import { describe, it, expect } from 'vitest';
import { render, screen } from '@/test/test-utils';
import { SuccessAlert, ErrorAlert } from './FormAlerts';

describe('FormAlerts', () => {
    describe('SuccessAlert', () => {
        it('renders message', () => {
            render(<SuccessAlert message="Success message" />);

            expect(screen.getByText('Success message')).toBeInTheDocument();
        });
    });

    describe('ErrorAlert', () => {
        it('renders message', () => {
            render(<ErrorAlert message="Error message" />);

            expect(screen.getByText('Error message')).toBeInTheDocument();
        });
    });
});
