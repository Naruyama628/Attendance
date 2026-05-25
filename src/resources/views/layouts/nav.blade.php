<nav class="navigation">
    <a href="/attendance" class="navigation__link">
        勤怠
    </a>

    <a href="/attendance/list" class="navigation__link">
        勤怠一覧
    </a>

    <a href="/stamp_correction_request/list" class="navigation__link">
        申請
    </a>

    <form action="/logout" method="POST" class="navigation__logout-form">
        @csrf
        <button type="submit" class="navigation__logout-button">
            ログアウト
        </button>
    </form>
</nav>