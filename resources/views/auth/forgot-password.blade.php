<h1>Забыл пароль</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route("password.email") }}" method="post">
    @csrf
    <input type="text" name="email" placeholder="E-Mail"><br>
    <input type="submit" value="Отправить">
</form>
