<script type="text/javascript">
    var app = {
        API_URL: '{{ url('/api') }}',
        BASE_PATH: '{{ url('/') }}',
        // Path-only for React Router basename (e.g., '/seminarios' or '')
        ROUTER_BASE: '{{ parse_url(url('/'), PHP_URL_PATH) ?? '' }}',
        RECAPTCHA_SITE_KEY: '{{ config('services.recaptcha.site_key') }}',
        GA_MEASUREMENT_ID: '{{ config('services.google_analytics.measurement_id') }}',
    };
</script>
@if(config('services.google_analytics.measurement_id'))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ config('services.google_analytics.measurement_id') }}', { send_page_view: false });
</script>
@endif