<aside class="main-sidebar">
    <!-- Brand Logo -->
    <div class="logo" style="display:none;">
        <a href="#" class="brand-link"><img src="{!! url('assets/images/fursaa_newLogo.png') !!}" alt="Fursa Logo" class="img-logo" style="width:220px;"></a>
    </div>
    <h1 class="panel-title">{{strtoupper(auth()->user()->roles[0]->name ?? '')}} PANEL</h1>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav" role="menu">

                @auth

                <li class="nav-item">
                    <a href="{{ auth()->user()->can('dashboard') ? route('orders.dashboard') : url('') }}" class="nav-link"> Dashboard </a>
                </li>

                @if(auth()->user()->can('users.index') || auth()->user()->can('roles.index'))
                <li class="nav-item">
                    <a href="#" class="nav-link"> User Management <i class="bi bi-chevron-down"></i></a>
                    <ul class="nav nav-dropdown">
                        @if(auth()->user()->can('users.index'))
                            <li class="nav-item"><a href="{{ route('users.index') }}" class="nav-link"> Users </a></li>
                        @endif

                        @if(auth()->user()->can('roles.index'))
                            <li class="nav-item"><a href="{{ route('roles.index') }}" class="nav-link"> Roles </a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if(auth()->user()->can('stores.index') || auth()->user()->can('vehicles.index'))
                <li class="nav-item">
                    <a href="#" class="nav-link"> Branch Management <i class="bi bi-chevron-down"></i></a>
                    <ul class="nav nav-dropdown">
                        @if(auth()->user()->can('stores.index'))
                            <li class="nav-item"><a href="{{ route('stores.index') }}" class="nav-link"> Locations </a></li>
                        @endif

                        @if(auth()->user()->can('vehicles.index'))
                            <li class="nav-item"><a href="{{ route('vehicles.index') }}" class="nav-link"> Vehicles </a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @auth
                    <li class="nav-item">
                        <a href="#" class="nav-link"> Ledger Management <i class="bi bi-chevron-down"></i></a>
                        <ul class="nav nav-dropdown">
                            <li class="nav-item"><a href="{{ route('ledger.index') }}" class="nav-link"> Dashboard </a></li>
                            <li class="nav-item"><a href="{{ route('payments.index') }}" class="nav-link"> Payments </a>
                            </li>
                        </ul>
                    </li>
                @endauth

                @if(auth()->user()->can('order-categories.index') || auth()->user()->can('order-units.index') || auth()->user()->can('order-products.index') || auth()->user()->can('orders.index') || auth()->user()->can('bulk-price-management.index') || auth()->user()->can('discount-management.index'))
                <li class="nav-item">
                    <a href="#" class="nav-link"> Bulk Order Management <i class="bi bi-chevron-down"></i></a>
                    <ul class="nav nav-dropdown">
                        @if(auth()->user()->can('order-categories.index'))
                            <li class="nav-item"><a href="{{ route('order-categories.index') }}" class="nav-link"> Categories </a></li>
                        @endif

                        @if(auth()->user()->can('order-units.index'))
                            <li class="nav-item"><a href="{{ route('order-units.index') }}" class="nav-link"> Units </a></li>
                        @endif

                        @if(auth()->user()->can('order-products.index'))
                            <li class="nav-item"><a href="{{ route('order-products.index') }}" class="nav-link"> Products </a></li>
                        @endif

                        @if(auth()->user()->can('pricing-tiers.index'))
                        <li class="nav-item"><a href="{{ route('pricing-tiers.index') }}" class="nav-link"> Pricing Tiers </a></li>
                        @endif
                        
                        @if(auth()->user()->can('other-items.index'))
                            <li class="nav-item"><a href="{{ route('other-items.index') }}" class="nav-link"> Other Items </a></li>
                        @endif

                        @if(auth()->user()->can('bulk-price-management.index'))
                            <li class="nav-item"><a href="{{ route('bulk-price-management.index') }}" class="nav-link"> Bulk Price Management </a></li>
                        @endif

                        @if(auth()->user()->can('orders.index'))
                            <li class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link"> Orders </a></li>
                        @endif

                        @if(auth()->user()->can('utencil-report.index'))
                            <li class="nav-item"><a href="{{ route('utencil-report.index') }}" class="nav-link"> Utencil Report </a></li>
                        @endif

                        @if(auth()->user()->can('handling-instructions.index'))
                            <li class="nav-item"><a href="{{ route('handling-instructions.index') }}" class="nav-link"> Handling Instruction </a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if(auth()->user()->can('settings.edit') || auth()->user()->can('tax-slabs.index'))
                <li class="nav-item">
                    <a href="#" class="nav-link"> Settings <i class="bi bi-chevron-down"></i></a>
                    <ul class="nav nav-dropdown">
                        @if(auth()->user()->can('tax-slabs.index'))
                            <li class="nav-item"><a href="{{ route('tax-slabs.index') }}" class="nav-link"> Tax Slabs </a></li>
                        @endif

                        @if(auth()->user()->can('settings.edit'))
                            <li class="nav-item"><a href="{{ route('settings.edit') }}" class="nav-link"> Settings </a></li>
                        @endif
                    </ul>
                </li>
                @endif

                <li class="nav-item">
                    <ul class="nav nav-dropdown">
                        <li class="nav-item"><a href="{{ route('logout') }}" class="nav-link">Logout</a></li>
                    </ul>
                </li>
                @endauth

            </ul>
        </nav>
        
        <div class="version"><img src="{!! url('assets/images/version.svg') !!}"> VERSION 1.0.0</div>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>