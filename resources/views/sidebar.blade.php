@if(count($blog['posts']))
    <h4>From the blog</h4>
    <p>
        @foreach(array_slice($blog['posts'], 0, 5) as $post)
            <a href="@url($post['path'])">{{ $post['title'] }}</a><br>
        @endforeach
    </p>
    <p>
        Want to read more? <br>
        <a href="@url('blog')">Check all posts</a>
    </p>

    <footer class="mt-2 mb-1">
        @include('branding')
    </footer>
@endif
