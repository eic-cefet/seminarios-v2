import { analytics } from './analytics';

describe('analytics', () => {
    let gtagSpy: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        gtagSpy = vi.fn();
        window.gtag = gtagSpy;
    });

    afterEach(() => {
        delete window.gtag;
        app.GA_MEASUREMENT_ID = '';
    });

    describe('pageview', () => {
        it('calls gtag when GA is configured', () => {
            app.GA_MEASUREMENT_ID = 'G-TEST123';
            analytics.pageview('/home', 'Home');
            expect(gtagSpy).toHaveBeenCalledWith('event', 'page_view', {
                page_path: '/home',
                page_title: 'Home',
            });
        });

        it('does nothing when GA_MEASUREMENT_ID is empty', () => {
            app.GA_MEASUREMENT_ID = '';
            analytics.pageview('/home');
            expect(gtagSpy).not.toHaveBeenCalled();
        });

        it('does nothing when gtag is undefined', () => {
            app.GA_MEASUREMENT_ID = 'G-TEST123';
            delete window.gtag;
            expect(() => analytics.pageview('/home')).not.toThrow();
        });
    });

    describe('event', () => {
        it('calls gtag with event name and params', () => {
            app.GA_MEASUREMENT_ID = 'G-TEST123';
            analytics.event('seminar_register', { seminar_id: '1' });
            expect(gtagSpy).toHaveBeenCalledWith('event', 'seminar_register', {
                seminar_id: '1',
            });
        });

        it('calls gtag with only event name', () => {
            app.GA_MEASUREMENT_ID = 'G-TEST123';
            analytics.event('click');
            expect(gtagSpy).toHaveBeenCalledWith('event', 'click', undefined);
        });

        it('does nothing when GA is not configured', () => {
            app.GA_MEASUREMENT_ID = '';
            analytics.event('click');
            expect(gtagSpy).not.toHaveBeenCalled();
        });
    });
});
