<a
    data-turbo="{{ var_export($turbo) }}"
    {{ $attributes }}
>
    @isset($icon)
        <x-orbit-icon :path="$icon" class="overflow-visible"/>
    @endisset

    {{ $name ?? '' }}
</a>
