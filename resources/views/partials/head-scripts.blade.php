<script type="text/javascript">
    var app = {
        API_URL: '{{ url('/api') }}',
        BASE_PATH: '{{ url('/') }}',
        // Path-only for React Router basename (e.g., '/seminarios' or '')
        ROUTER_BASE: '{{ parse_url(url('/'), PHP_URL_PATH) ?? '' }}',
        RECAPTCHA_SITE_KEY: '{{ config('services.recaptcha.site_key') }}',
    };
</script>