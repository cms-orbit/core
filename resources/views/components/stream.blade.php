@props(['target', 'action', 'push', 'rule' => \CmsOrbit\Core\Facades\Dashboard::isPartialRequest()])


@if(filter_var($rule, FILTER_VALIDATE_BOOLEAN))
    @fragment($target)
        <turbo-stream target="{{ $target }}" action="{{ $action ?? 'replace' }}">
            <template>
                {!! $slot !!}
            </template>
        </turbo-stream>
    @endfragment
@elseif(!empty($push))
    @push($push)
        {!! $slot !!}
    @endpush
@else
    {!! $slot !!}
@endif
