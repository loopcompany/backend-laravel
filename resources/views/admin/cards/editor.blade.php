<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Card Editor</title>
    <link rel="stylesheet" href="https://static.neshan.org/sdk/leaflet/1.4.0/leaflet.css" />
    <script src="https://static.neshan.org/sdk/leaflet/1.4.0/leaflet.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
    <div
        id="card-editor-app"
        data-editor-url="{{ $editorDataUrl }}"
        data-save-url="{{ $saveEditorUrl }}"
        data-upload-url="{{ $uploadMediaUrl }}"
        data-create-card-url="{{ $createCardUrl }}"
    ></div>
</body>
</html>
