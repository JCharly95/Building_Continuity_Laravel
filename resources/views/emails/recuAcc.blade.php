<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperacion de Acceso</title>
</head>
<body>
    <div>
        <p style="text-justify=distribute;">
            Buen día estimado usuario.<br><br>
            Este correo fue enviado, ya que, el usuario <strong>{{ $infoCorreo['nombre'] }} {{ $infoCorreo['apePat'] }} {{ $infoCorreo['apeMat'] }}</strong> solicitó la recuperación de contraseña en el sistema <strong>Building Continuity</strong> y en el presente correo se hace llegar en enlace de recuperación, a continuacion:<br><br>
            <a href="{{ $infoCorreo['linkRecuCor'] }}" target="_blank">Actualizar Contraseña de {{ $infoCorreo['nombre'] }} {{ $infoCorreo['apePat'] }} {{ $infoCorreo['apeMat'] }}</a><br><br>
            Gracias por su atención y que tenga buen día.<br><br>
            <strong>NOTA: Si no fue usted quien solicitó la recuperación, favor de contactar con el administrador para obtener más información acerca de su cuenta.</strong><br><br>
            <strong>Favor de NO responder a este correo</strong>, ya que, es un medio únicamente informativo.
        </p>
    </div>
</body>
</html>