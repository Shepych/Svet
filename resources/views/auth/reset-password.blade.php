<h1>Восстановление пароля</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route("password.update") }}" method="POST">
    @csrf
    <input type="text" name="email" placeholder="E-Mail"><br>
    <input type="password" name="password" placeholder="Password"><br>
    <input type="password" name="password_confirmation" placeholder="Password confirm"><br>
    <input type="hidden" name="token" value="{{ app('request')->token }}">
    <input type="submit" value="Сменить пароль">
</form>
