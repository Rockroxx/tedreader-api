<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TED Reader</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div id="app"></div>
<div style="clear:both"></div>
<script>
    window.api_url = "https://api.tedreader.co.uk/";
    @if(isset($total))
        {{  "window.total = ".$total }};
    @endif
    @if(isset($results))
        {{ "window.results = ".$total }};
    @endif
    @if(isset($notice))
        {{ "window.notice = ".$notice }};
    @endif
    @if(isset($contacts))
        {{ "window.contacts = ".$contacts }};
    @endif
    @if(isset($awards))
        {{ "window.awards = ".$awards }};
    @endif
    @if(isset($lots))
        {{ "window.lots = ".$lots }};
    @endif
    @if(isset($categories))
        {{ "window.categories = ".$categories }};
    @endif
    @if(isset($category_list))
        {{ "window.categories_list = ".$category_list }};
    @endif

</script>
<script src="/dist/build.js"></script>
</body>
</html>
