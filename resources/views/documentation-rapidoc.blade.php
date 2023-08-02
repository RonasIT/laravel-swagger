<!doctype html> <!-- Important: must specify -->
<html>
    <head>
        <meta charset="utf-8"> <!-- Important: rapi-doc uses utf8 characters -->
        <script src="{{ config('auto-doc.global_prefix') }}/auto-doc/rapidoc-min.js"></script>
    </head>
    <body>
        <rapi-doc
            spec-url="{{ config('auto-doc.global_prefix') }}/auto-doc/documentation"
            render-style="focused"
            layout="column">
        </rapi-doc>
    </body>
</html>
