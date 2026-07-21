@props([
    'genres' => [],
    'activeSlug' => null,
])

<nav class="ts-drama-tabs" aria-label="{{ __('ui.catalog.quick_categories') }}">
    <div class="ts-drama-tabs__track">
        <a href="{{ route('home') }}#catalog-top"
           class="ts-drama-tabs__chip {{ ! $activeSlug ? 'is-active' : '' }}">
            {{ __('ui.browse.all') }}
        </a>
        @foreach($genres as $genre)
            @php $slug = $genre->slugForLocale(); @endphp
            <a href="{{ route('browse', ['genre' => $slug]) }}"
               class="ts-drama-tabs__chip {{ $activeSlug === $slug ? 'is-active' : '' }}">
                {{ $genre->nameForLocale() }}
            </a>
        @endforeach
    </div>
</nav>
