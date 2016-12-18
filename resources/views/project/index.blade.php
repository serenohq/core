@extends('base')

@section('body')
    <div class="container py-2">
        <div class="card-columns">
            @foreach ($projects as $project)
                <div class="card">
                    @if (isset($project->photo))
                        <img src="@url($project->photo)" class="card-img-top img-fluid">
                    @endif

                    <div class="card-block">
                        <h4 class="card-title">{{ $project->title }}</h4>

                        @markdown {!! $project->content !!} @endmarkdown

                        <a href="{{ $project->repo }}" class="card-link" target="_blank">
                            <i class="fa fa-fw fa-github"></i>
                            <span class="text-muted">Source</span>
                        </a>
                        <a href="{{ $project->docs }}" class="card-link" target="_blank">
                            <i class="fa fa-fw fa-book"></i>
                            <span class="text-muted">Documentation</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
