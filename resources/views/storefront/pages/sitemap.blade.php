{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ url('/') }}</loc></url>
    <url><loc>{{ url('/shop') }}</loc></url>
    @if($hasPublishedPosts)
        <url><loc>{{ url('/blog') }}</loc></url>
    @endif
    @foreach($pages as $page)
        <url><loc>{{ url('/'.$page->slug) }}</loc></url>
    @endforeach
    @foreach($categories as $category)
        <url><loc>{{ $category->url() }}</loc></url>
    @endforeach
    @foreach($products as $product)
        <url><loc>{{ url('/product/'.$product->slug) }}</loc><lastmod>{{ $product->updated_at->toDateString() }}</lastmod></url>
    @endforeach
    @foreach($posts as $post)
        <url><loc>{{ url('/blog/'.$post->slug) }}</loc><lastmod>{{ $post->updated_at->toDateString() }}</lastmod></url>
    @endforeach
</urlset>
