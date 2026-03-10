@php
    $user = auth($guard)->user();
@endphp

@if ($user && (!isset($permissions) || $user->canAny($permissions)))
    <li>
        <a href="javascript:void(0);" data-toggle="collapse" data-target="#{{ $id }}">
            <div class="pull-left">
                <i class="{{ $icon }}"></i>
                <span class="right-nav-text">{{ $label }}</span>
            </div>
            <div class="pull-right"><i class="ti-plus"></i></div>
            <div class="clearfix"></div>
        </a>
        <ul id="{{ $id }}" class="collapse" data-parent="#sidebarnav">
        @foreach ($items as $item)
    @if (is_array($item) && (!isset($item['permission']) || $user->can($item['permission'])))
        <li>
            <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
        </li>
    @endif
@endforeach

        </ul>
    </li>
@endif
