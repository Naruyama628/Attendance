<nav class="navigation">
    @auth
        @if(auth()->user()->role === 'admin')
            <a href="/admin/attendance/list" class="navigation__link">
                勤怠一覧
            </a>

            <a href="/admin/staff/list" class="navigation__link">
                スタッフ一覧
            </a>

            <a href="/stamp_correction_request/list" class="navigation__link">
                申請一覧
            </a>


        @else
            <a href="/attendance" class="navigation__link">
                勤怠
            </a>

            <a href="/attendance/list" class="navigation__link">
                勤怠一覧
            </a>

            <a href="/stamp_correction_request/list" class="navigation__link">
                申請
            </a>

        @endif

        <form action="/logout" method="POST" class="navigation__logout-form">
            @csrf
            <button type="submit" class="navigation__logout-button">
                ログアウト
            </button>
        </form>
    @endauth
</nav>