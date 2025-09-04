<?php
session_start();
session_unset(); // limpa variáveis de sessão
session_destroy(); // destrói a sessão

header("Location: index.php");
exit();
