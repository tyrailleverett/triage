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

    @php
        $currentAgent = auth()->user();
        $triageConfig = [
            'dashboardPath' => '/'.ltrim((string) config('triage.path', 'triage'), '/'),
            'apiBasePath' => '/'.ltrim((string) config('triage.path', 'triage'), '/').'/api',
            'csrfToken' => csrf_token(),
            'currentAgent' => $currentAgent !== null
                ? [
                    'id' => (string) $currentAgent->getAuthIdentifier(),
                    'name' => (string) data_get($currentAgent, 'name', 'Agent'),
                    'email' => (string) data_get($currentAgent, 'email', ''),
                    'role' => (string) data_get($currentAgent, 'role', 'Support Agent'),
                ]
                : null,
        ];
    @endphp

    <script>
        window.TriageConfig = {!! json_encode($triageConfig, JSON_THROW_ON_ERROR) !!};
    </script>

    <script type="module" src="/vendor/triage/assets/app.js"></script>
</body>

</html>