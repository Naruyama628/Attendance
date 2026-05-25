<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layouts/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layouts/nav.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__logo">
                <a href="/" class="header__link">
                    <img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH">
                </a>
            </h1>

            <div class="header__nav">
                <!-- auth -->
                @include('layouts.nav')
                <!-- endauth -->
            </div>
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>
</body>

</html>