<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('home') }}" class="app-brand-link">
            <span class="app-brand-text demo menu-text fw-bolder text-primary ms-2">AcadOps</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @foreach($menuItems as $item)
            @if(isset($item['children']))
                <li class="menu-item {{ $item['active'] ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons {{ $item['icon'] }}"></i>
                        <div>{{ $item['title'] }}</div>
                    </a>
                    <ul class="menu-sub">
                        @foreach($item['children'] as $child)
                            <li class="menu-item {{ $child['active'] ? 'active' : '' }}">
                                <a href="{{ $child['route'] }}" class="menu-link">
                                    <i class="menu-icon tf-icons {{ $child['icon'] }}"></i>
                                    <div>{{ $child['title'] }}</div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li class="menu-item {{ $item['active'] ? 'active' : '' }}">
                    <a href="{{ $item['route'] }}" class="menu-link">
                        <i class="menu-icon tf-icons {{ $item['icon'] }}"></i>
                        <div>{{ $item['title'] }}</div>
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</aside>
