<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#{{ $id }}">
        <div class="pull-left">
            <i class="fa {{ $icon }}"></i>
            <span class="right-nav-text">{{ $label }}</span>
        </div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="{{ $id }}" class="collapse" data-parent="#sidebarnav">
        @foreach ($items as $item)
            <li><a href="{{ route($item['route']) }}">{{ $item['label'] }}</a></li>
        @endforeach
    </ul>
</li>
