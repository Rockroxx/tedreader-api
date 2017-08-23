<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TED Reader</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div id="app"></div>
<div style="clear:both"></div>
<script type="text/javascript">
    window.api_url = "https://api.tedreader.co.uk/";
    @if(isset($total))
        {!! "window.total = ".$total !!};
    @endif
    @if(isset($results))
        {!! "window.results = ".$results !!};
    @endif
    @if(isset($notice))
        {!! "window.notice = ".$notice !!};
    @endif
    @if(isset($contacts))
        {!! "window.contacts = ".$contacts !!};
    @endif
    @if(isset($awards))
        {!! "window.awards = ".$awards !!};
    @endif
    @if(isset($lots))
        {!! "window.lots = ".$lots !!};
    @endif
    @if(isset($categories))
        {!! "window.categories = ".$categories !!};
    @endif
    @if(isset($category_list))
        {!! "window.categories_list = ".$category_list !!};
    @endif

</script>
<noscript id="deferred-styles">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>
</noscript>
<script>
    var loadDeferredStyles = function() {
        var addStylesNode = document.getElementById("deferred-styles");
        var replacement = document.createElement("div");
        replacement.innerHTML = addStylesNode.textContent;
        document.body.appendChild(replacement)
        addStylesNode.parentElement.removeChild(addStylesNode);
    };
    var raf = requestAnimationFrame || mozRequestAnimationFrame ||
        webkitRequestAnimationFrame || msRequestAnimationFrame;
    if (raf) raf(function() { window.setTimeout(loadDeferredStyles, 0); });
    else window.addEventListener('load', loadDeferredStyles);
</script>
<script src="/dist/build.js" ></script>
</body>
</html>
