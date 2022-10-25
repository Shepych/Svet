<!DOCTYPE html>
<html>
<head>
    <title>Svet</title>
    <style>
        @font-face {
            font-family: 'Roboto';
            src: url('/fonts/Roboto/Roboto-Bold.ttf');
        }
        body,html {
            height:100%;
            width: 100%;
            background: rgb(185,187,212);
            background: linear-gradient(90deg, rgba(185,187,212,1) 0%, rgba(95,102,160,1) 84%);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-family: 'Roboto';
        }

        .documentation {
            background-color: #dee1f0;
            background-color: #454b80;
            background-color: #5e65a0;
            background-color: #5e65a0;
            display:flex;
            justify-content:center;
            align-items:center;
            flex-direction:column;
            border-radius:20px;
            /*border:1px solid #454b80;*/
            /*padding:26px;*/
            font-size:20px;
            text-decoration: none;
            color: black;
            box-shadow:0 0 2px black;
            height: 120px;
            min-width: 120px;
            transition: 0.3s;
        }

        .documentation:hover {
            background-color: #23164e;
            background-color: #474283;
            /*border-color: #6e31ff;*/
            /*box-shadow: 0 0 2px black, inset 0 0 30px #9449af;*/
        }

        .doc__section {
            display:flex;
            justify-content:center;
            align-items:center;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="/js/pagePiling.js-master/jquery.pagepiling.css">
</head>
<body>
    <img width="260" src="/svet.png" style="margin-top:30px;margin-bottom: 30px">
    <h2 style="margin:0;margin-bottom: 30px;color:#301e67">API сервер для приложения « <span style="color:white;text-shadow: #301e67 1px 0 10px;">SVET</span> »</h2>
    <div class="doc__section">
        <a href="/api/documentation" class="documentation">
            <img width="66" src="/api.png">
        </a>
        <a href="https://github.com/Shepych/Svet" style="margin-left: 30px;" class="documentation"><img style="width:84px" src="/Octocat.png"></a>
        <a href="https://miro.com/app/board/uXjVPWMy1ng=/" class="documentation" style="margin-left: 30px"><img width="76" src="/miro.png"></a>
    </div>
</body>
</html>
