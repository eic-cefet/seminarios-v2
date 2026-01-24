<script type="text/javascript">
    var app = {
        API_URL: '{{ url('/api') }}',
        BASE_PATH: '{{ url('/') }}',
        RECAPTCHA_SITE_KEY: '{{ config('services.recaptcha.site_key') }}',
    };
</script>