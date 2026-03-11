<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Triage — {{ config('app.name') }}</title>
        <link rel="stylesheet" href="/vendor/triage/assets/app.css">
    </head>
    <body>
        <div id="triage-app"></div>

        <script>
            window.TriageConfig = {
                dashboardPath: "{{ config('triage.dashboard.path', '/triage') }}",
                apiBasePath: "{{ config('triage.dashboard.path', '/triage') }}/api",
                csrfToken: "{{ csrf_token() }}"
            };
        </script>

        <script type="module" src="/vendor/triage/assets/app.js"></script>
    </body>
</html>
