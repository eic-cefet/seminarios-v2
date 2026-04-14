@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{!! htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') !!}</loc>
@if($url['lastmod'])
        <lastmod>{!! htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') !!}</lastmod>
@endif
    </url>
@endforeach
</urlset>
