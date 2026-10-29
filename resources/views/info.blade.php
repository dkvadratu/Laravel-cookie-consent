@once
    <style>
        .cookie-consent-list__table { margin: 1rem 0 2rem; overflow-x: auto; }
        .cookie-consent-list table { width: 100%; min-width: 35rem; border-collapse: collapse; }
        .cookie-consent-list th, .cookie-consent-list td { padding: .75rem 1rem; border-bottom: 1px solid; border-color: color-mix(in srgb, currentColor 20%, transparent); text-align: left; vertical-align: top; }
        .cookie-consent-list th:first-child, .cookie-consent-list td:first-child { padding-left: 0; }
        .cookie-consent-list th:last-child, .cookie-consent-list td:last-child { padding-right: 0; white-space: nowrap; }
    </style>
@endonce

<div class="cookie-consent-list">
    @foreach($cookies->getCategories() as $category)
        <h3>{{ $category->title }}</h3>
        <div class="cookie-consent-list__table">
            <table>
                <thead>
                    <tr>
                        <th scope="col">@lang('cookieConsent::cookies.cookie')</th>
                        <th scope="col">@lang('cookieConsent::cookies.purpose')</th>
                        <th scope="col">@lang('cookieConsent::cookies.duration')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($category->getCookies() as $cookie)
                        <tr>
                            <td>{{ $cookie->name }}</td>
                            <td>{{ $cookie->description }}</td>
                            <td>{{ \Carbon\CarbonInterval::minutes($cookie->duration)->cascade() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
