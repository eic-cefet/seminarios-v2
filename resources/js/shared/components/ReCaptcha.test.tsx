import { render, screen } from '@/test/test-utils';
import { ReCaptcha, isRecaptchaEnabled } from './ReCaptcha';

vi.mock('react-google-recaptcha', () => ({
    default: ({ sitekey, onChange, onExpired }: { sitekey: string; onChange: (token: string) => void; onExpired?: () => void }) => (
        <div data-testid="recaptcha" data-sitekey={sitekey}>
            <button onClick={() => onChange('test-token')}>Verify</button>
            {onExpired && <button onClick={onExpired}>Expire</button>}
        </div>
    ),
}));

describe('isRecaptchaEnabled', () => {
    it('returns true when RECAPTCHA_SITE_KEY is set', () => {
        app.RECAPTCHA_SITE_KEY = 'test-key';
        expect(isRecaptchaEnabled()).toBe(true);
    });

    it('returns false when RECAPTCHA_SITE_KEY is empty', () => {
        app.RECAPTCHA_SITE_KEY = '';
        expect(isRecaptchaEnabled()).toBe(false);
    });
});

describe('ReCaptcha', () => {
    it('renders reCAPTCHA when enabled', () => {
        app.RECAPTCHA_SITE_KEY = 'test-key';
        render(<ReCaptcha onVerify={vi.fn()} />, { routerProps: { initialEntries: ['/'] } });
        expect(screen.getByTestId('recaptcha')).toBeInTheDocument();
    });

    it('returns null when disabled', () => {
        app.RECAPTCHA_SITE_KEY = '';
        const { container } = render(<ReCaptcha onVerify={vi.fn()} />, { routerProps: { initialEntries: ['/'] } });
        expect(container.firstChild).toBeNull();
    });

    it('calls onVerify when token received', async () => {
        app.RECAPTCHA_SITE_KEY = 'test-key';
        const onVerify = vi.fn();
        render(<ReCaptcha onVerify={onVerify} />, { routerProps: { initialEntries: ['/'] } });

        screen.getByText('Verify').click();
        expect(onVerify).toHaveBeenCalledWith('test-token');
    });

    afterEach(() => {
        app.RECAPTCHA_SITE_KEY = 'test-key';
    });
});
