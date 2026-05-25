<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>{{ tenant('name') }} - LEXA</title>
    <style>
        body { font-family: sans-serif; max-width: 640px; margin: 4rem auto; padding: 0 1rem; line-height: 1.7; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>{{ tenant('name') }}</h1>
    <p>المستأجر النشط (Active tenant id): <code>{{ tenant('id') }}</code></p>
    <p>This dashboard placeholder will be replaced by the real RTL shell + Livewire components in the next milestone.</p>
</body>
</html>
